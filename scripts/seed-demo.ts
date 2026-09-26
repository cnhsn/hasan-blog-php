/**
 * Opt-in LOCAL fixtures only. Refuses to run in production and refuses
 * unless ALLOW_DEMO_SEED=true (docs/OPERATIONS.md). Everything it
 * creates is clearly labelled sample content and uses `sample-` slugs,
 * so a re-run replaces exactly its own documents and nothing else.
 *
 * Production collections stay empty: this is never part of the normal
 * setup sequence.
 */
import type { SanitizedConfig } from 'payload'
import { getPayload, type Payload } from 'payload'
import sharp from 'sharp'

import { getEnv } from '../src/lib/env.ts'

const SAMPLE_PREFIX = 'sample-'

function lexical(paragraphs: string[]) {
  return {
    root: {
      type: 'root',
      direction: 'ltr' as const,
      format: '' as const,
      indent: 0,
      version: 1,
      children: paragraphs.map((text) => ({
        type: 'paragraph',
        direction: 'ltr' as const,
        format: '' as const,
        indent: 0,
        version: 1,
        textFormat: 0,
        children: [
          { type: 'text', detail: 0, format: 0, mode: 'normal', style: '', text, version: 1 },
        ],
      })),
    },
  }
}

async function clearExistingSamples(payload: Payload) {
  for (const collection of ['posts', 'projects'] as const) {
    await payload.delete({
      collection,
      overrideAccess: true,
      where: { slug: { like: SAMPLE_PREFIX } },
    })
  }
  await payload.delete({
    collection: 'redirects',
    overrideAccess: true,
    where: { sourcePath: { like: SAMPLE_PREFIX } },
  })
  for (const collection of ['categories', 'tags'] as const) {
    await payload.delete({
      collection,
      overrideAccess: true,
      where: { slug: { like: SAMPLE_PREFIX } },
    })
  }
  await payload.delete({
    collection: 'media',
    overrideAccess: true,
    where: { alt: { like: 'Sample' } },
  })
}

async function main(config: SanitizedConfig) {
  const env = getEnv()

  if (env.isProduction) {
    console.error('Refusing to seed demo content: NODE_ENV is production.')
    process.exit(1)
  }
  if (!env.allowDemoSeed) {
    console.error('Refusing to seed demo content: set ALLOW_DEMO_SEED=true in .env to opt in.')
    process.exit(1)
  }

  const payload = await getPayload({ config })
  await clearExistingSamples(payload)

  const categories = await Promise.all(
    [
      ['Sample: Engineering', `${SAMPLE_PREFIX}engineering`],
      ['Sample: Notes', `${SAMPLE_PREFIX}notes`],
    ].map(([name, slug]) =>
      payload.create({
        collection: 'categories',
        overrideAccess: true,
        data: { name: name!, slug: slug! },
      }),
    ),
  )

  const tags = await Promise.all(
    [
      ['Sample: TypeScript', `${SAMPLE_PREFIX}typescript`],
      ['Sample: Data', `${SAMPLE_PREFIX}data`],
    ].map(([name, slug]) =>
      payload.create({
        collection: 'tags',
        overrideAccess: true,
        data: { name: name!, slug: slug! },
      }),
    ),
  )

  const coverBuffer = await sharp({
    create: { width: 1600, height: 1000, channels: 3, background: { r: 21, g: 42, b: 238 } },
  })
    .png()
    .toBuffer()

  const cover = await payload.create({
    collection: 'media',
    overrideAccess: true,
    data: { alt: 'Sample cover image (local demo fixture)', visibility: 'public' },
    file: {
      data: coverBuffer,
      mimetype: 'image/png',
      name: 'sample-cover.png',
      size: coverBuffer.byteLength,
    },
  })

  // 11 published posts exercises pagination at the default page size of 9.
  for (let index = 1; index <= 11; index += 1) {
    await payload.create({
      collection: 'posts',
      overrideAccess: true,
      data: {
        title: `Sample post ${index}`,
        slug: `${SAMPLE_PREFIX}post-${index}`,
        excerpt: `Sample excerpt ${index}. This is local demo content, not real writing.`,
        content: lexical([
          `This is sample post ${index}. It exists only to exercise the local UI.`,
          'Sample paragraph so reading time and layout have something to work with.',
        ]),
        authorName: 'Hasan',
        categories: [categories[index % 2]!.id],
        tags: [tags[index % 2]!.id],
        cover: index === 1 ? cover.id : undefined,
        featured: index === 1,
        _status: 'published',
      },
    })
  }

  await payload.create({
    collection: 'posts',
    overrideAccess: true,
    data: {
      title: 'Sample unpublished draft',
      slug: `${SAMPLE_PREFIX}unpublished-draft`,
      excerpt: 'Sample draft excerpt — must never appear on public pages.',
      content: lexical(['This draft must not be publicly visible.']),
      authorName: 'Hasan',
      _status: 'draft',
    },
  })

  await payload.create({
    collection: 'projects',
    overrideAccess: true,
    data: {
      title: 'Sample project',
      slug: `${SAMPLE_PREFIX}project`,
      shortDescription: 'A sample project used for local UI checks only.',
      caseStudy: lexical(['Sample case study body.']),
      technologies: ['TypeScript', 'PostgreSQL'],
      lifecycleStatus: 'in-progress',
      startDate: new Date('2026-01-15').toISOString(),
      cover: cover.id,
      gallery: [{ media: cover.id, caption: 'Sample gallery image' }],
      _status: 'published',
    },
  })

  await payload.create({
    collection: 'projects',
    overrideAccess: true,
    data: {
      title: 'Sample draft project',
      slug: `${SAMPLE_PREFIX}draft-project`,
      shortDescription: 'Sample draft project — must never appear on public pages.',
      caseStudy: lexical(['Draft case study body.']),
      lifecycleStatus: 'idea',
      startDate: new Date('2026-02-01').toISOString(),
      _status: 'draft',
    },
  })

  console.log('Seeded local demo fixtures: 11 published posts, 1 draft post, 2 projects, 1 image.')
  console.log('All sample slugs start with "sample-". Re-running replaces only those documents.')
}

export async function script(config: SanitizedConfig): Promise<void> {
  try {
    await main(config)
    process.exit(0)
  } catch (error) {
    console.error('Demo seed failed:', error instanceof Error ? error.message : error)
    process.exit(1)
  }
}
