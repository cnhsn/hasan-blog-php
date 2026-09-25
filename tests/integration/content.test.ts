import { execFileSync } from 'node:child_process'

import type { Payload } from 'payload'
import { beforeAll, beforeEach, describe, expect, it } from 'vitest'

import { getTestPayload, lexicalParagraph, pngBuffer, resetContent } from '../helpers/payload'

/** Object count reported by the Garage service itself, not by the app. */
function bucketObjectCount(bucket: string): number {
  const out = execFileSync(
    'docker',
    ['compose', 'exec', '-T', 'garage', '/garage', 'bucket', 'info', bucket],
    { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] },
  )
  const match = out.match(/Objects:\s+(\d+)/)
  return match ? Number(match[1]) : 0
}

/**
 * Content rules that must hold in the database, not just the editor:
 * slug validation and history, publish-time media checks, asset
 * integrity and media privacy (docs/ACCEPTANCE.md A11-A15).
 */
describe('content rules', () => {
  let payload: Payload

  beforeAll(async () => {
    payload = await getTestPayload()
  })

  beforeEach(async () => {
    await resetContent()
  })

  function postData(overrides: Record<string, unknown> = {}) {
    return {
      title: 'First post',
      slug: 'first-post',
      excerpt: 'An excerpt.',
      content: lexicalParagraph('Body text.'),
      authorName: 'Hasan',
      _status: 'published' as const,
      ...overrides,
    }
  }

  async function createMedia(visibility: 'private' | 'public' = 'private', name = 'test.png') {
    return payload.create({
      collection: 'media',
      overrideAccess: true,
      data: { alt: 'A test image', visibility },
      file: {
        data: await pngBuffer(),
        mimetype: 'image/png',
        name,
        size: (await pngBuffer()).byteLength,
      },
    })
  }

  describe('slugs', () => {
    it('auto-generates a slug from the title when none is given', async () => {
      const post = await payload.create({
        collection: 'posts',
        overrideAccess: true,
        data: postData({ title: 'Generated Slug Please', slug: undefined }),
      })
      expect(post.slug).toBe('generated-slug-please')
    })

    it('rejects a duplicate slug', async () => {
      await payload.create({ collection: 'posts', overrideAccess: true, data: postData() })
      await expect(
        payload.create({
          collection: 'posts',
          overrideAccess: true,
          data: postData({ title: 'Second post' }),
        }),
      ).rejects.toThrow()
    })

    it('rejects reserved and malformed slugs', async () => {
      await expect(
        payload.create({ collection: 'posts', overrideAccess: true, data: postData({ slug: 'admin' }) }),
      ).rejects.toThrow()

      await expect(
        payload.create({
          collection: 'posts',
          overrideAccess: true,
          data: postData({ slug: 'Not A Slug' }),
        }),
      ).rejects.toThrow()
    })
  })

  describe('slug history redirects', () => {
    it('creates a permanent redirect when a published post is renamed', async () => {
      const post = await payload.create({
        collection: 'posts',
        overrideAccess: true,
        data: postData({ slug: 'original-slug' }),
      })

      await payload.update({
        collection: 'posts',
        id: post.id,
        overrideAccess: true,
        data: { slug: 'renamed-slug' },
      })

      const redirects = await payload.find({ collection: 'redirects', overrideAccess: true })
      expect(redirects.docs).toHaveLength(1)
      expect(redirects.docs[0]?.sourcePath).toBe('/blog/original-slug')
      expect(redirects.docs[0]?.targetPath).toBe('/blog/renamed-slug')
    })

    it('flattens a chain of renames instead of pointing at a stale hop', async () => {
      const post = await payload.create({
        collection: 'posts',
        overrideAccess: true,
        data: postData({ slug: 'slug-one' }),
      })

      await payload.update({
        collection: 'posts',
        id: post.id,
        overrideAccess: true,
        data: { slug: 'slug-two' },
      })
      await payload.update({
        collection: 'posts',
        id: post.id,
        overrideAccess: true,
        data: { slug: 'slug-three' },
      })

      const redirects = await payload.find({ collection: 'redirects', overrideAccess: true })
      const targets = Object.fromEntries(redirects.docs.map((d) => [d.sourcePath, d.targetPath]))

      expect(targets['/blog/slug-one']).toBe('/blog/slug-three')
      expect(targets['/blog/slug-two']).toBe('/blog/slug-three')
      // No redirect may point at something that is itself a redirect source.
      for (const target of Object.values(targets)) {
        expect(targets[target as string]).toBeUndefined()
      }
    })

    it('does not create a redirect when renaming a never-published draft', async () => {
      const post = await payload.create({
        collection: 'posts',
        overrideAccess: true,
        data: postData({ slug: 'draft-original', _status: 'draft' }),
      })

      await payload.update({
        collection: 'posts',
        id: post.id,
        overrideAccess: true,
        data: { slug: 'draft-renamed' },
      })

      const redirects = await payload.find({ collection: 'redirects', overrideAccess: true })
      expect(redirects.docs).toHaveLength(0)
    })

    it('deactivates redirects that would point at an unpublished post', async () => {
      const post = await payload.create({
        collection: 'posts',
        overrideAccess: true,
        data: postData({ slug: 'live-slug' }),
      })
      await payload.update({
        collection: 'posts',
        id: post.id,
        overrideAccess: true,
        data: { slug: 'live-slug-renamed' },
      })
      expect((await payload.find({ collection: 'redirects', overrideAccess: true })).docs).toHaveLength(1)

      await payload.update({
        collection: 'posts',
        id: post.id,
        overrideAccess: true,
        data: { _status: 'draft' },
      })

      const after = await payload.find({ collection: 'redirects', overrideAccess: true })
      expect(after.docs).toHaveLength(0)
    })
  })

  describe('publishedAt', () => {
    it('is set on first publish and preserved across later edits', async () => {
      const post = await payload.create({
        collection: 'posts',
        overrideAccess: true,
        data: postData({ _status: 'draft' }),
      })
      expect(post.publishedAt).toBeFalsy()

      const published = await payload.update({
        collection: 'posts',
        id: post.id,
        overrideAccess: true,
        data: { _status: 'published' },
      })
      const firstPublishedAt = published.publishedAt
      expect(firstPublishedAt).toBeTruthy()

      const edited = await payload.update({
        collection: 'posts',
        id: post.id,
        overrideAccess: true,
        data: { title: 'Edited title', publishedAt: new Date(0).toISOString() },
      })
      expect(edited.publishedAt).toBe(firstPublishedAt)
    })
  })

  describe('media', () => {
    it('defaults to private and hides private assets from anonymous reads', async () => {
      const media = await createMedia('private', 'private-asset.png')
      expect(media.visibility).toBe('private')

      const anon = await payload.find({
        collection: 'media',
        overrideAccess: false,
        where: { id: { equals: media.id } },
      })
      expect(anon.docs).toHaveLength(0)
    })

    it('exposes assets only once explicitly marked public, and hides them again on reversal', async () => {
      const media = await createMedia('public', 'public-asset.png')

      let anon = await payload.find({
        collection: 'media',
        overrideAccess: false,
        where: { id: { equals: media.id } },
      })
      expect(anon.docs).toHaveLength(1)

      await payload.update({
        collection: 'media',
        id: media.id,
        overrideAccess: true,
        data: { visibility: 'private' },
      })

      anon = await payload.find({
        collection: 'media',
        overrideAccess: false,
        where: { id: { equals: media.id } },
      })
      expect(anon.docs).toHaveLength(0)
    })

    it('writes real objects into the S3 service and generates resized variants', async () => {
      const before = bucketObjectCount('hasan-journal-test')

      const media = await payload.create({
        collection: 'media',
        overrideAccess: true,
        data: { alt: 'Round trip', visibility: 'public' },
        file: {
          data: await pngBuffer(1200, 800),
          mimetype: 'image/png',
          name: 'round-trip.png',
          size: (await pngBuffer(1200, 800)).byteLength,
        },
      })

      expect(media.filename).toBeTruthy()
      expect(media.width).toBe(1200)
      expect(media.height).toBe(800)
      // sharp-generated responsive variants
      expect(Object.keys(media.sizes ?? {})).toEqual(
        expect.arrayContaining(['thumbnail', 'medium', 'large']),
      )

      // The storage service itself confirms the bytes arrived: the
      // original plus each generated size.
      expect(bucketObjectCount('hasan-journal-test')).toBeGreaterThan(before)
    })

    it('rejects an unsupported file type even with an image extension', async () => {
      await expect(
        payload.create({
          collection: 'media',
          overrideAccess: true,
          data: { alt: 'Not an image', visibility: 'private' },
          file: {
            data: Buffer.from('#!/bin/sh\necho definitely not an image\n', 'utf8'),
            mimetype: 'image/png',
            name: 'fake.png',
            size: 40,
          },
        }),
      ).rejects.toThrow()
    })

    it('blocks deleting an asset that a post still references', async () => {
      const media = await createMedia('public', 'in-use.png')
      await payload.create({
        collection: 'posts',
        overrideAccess: true,
        data: postData({ slug: 'uses-media', cover: media.id }),
      })

      await expect(
        payload.delete({ collection: 'media', id: media.id, overrideAccess: true }),
      ).rejects.toThrow(/still in use/i)
    })

    it('refuses to publish a post whose referenced media is still private', async () => {
      const media = await createMedia('private', 'still-private.png')

      await expect(
        payload.create({
          collection: 'posts',
          overrideAccess: true,
          data: postData({ slug: 'private-cover', cover: media.id }),
        }),
      ).rejects.toThrow(/must be marked public/i)
    })
  })

  describe('rich text link safety', () => {
    it('rejects content containing a javascript: link', async () => {
      const malicious = {
        root: {
          type: 'root',
          direction: 'ltr' as const,
          format: '' as const,
          indent: 0,
          version: 1,
          children: [
            {
              type: 'paragraph',
              direction: 'ltr' as const,
              format: '' as const,
              indent: 0,
              version: 1,
              children: [
                {
                  type: 'link',
                  version: 1,
                  fields: { linkType: 'custom', url: 'javascript:alert(1)', newTab: false },
                  children: [{ type: 'text', text: 'click me', version: 1, format: 0, detail: 0, mode: 'normal', style: '' }],
                },
              ],
            },
          ],
        },
      }

      await expect(
        payload.create({
          collection: 'posts',
          overrideAccess: true,
          data: postData({ slug: 'malicious-link', content: malicious }),
        }),
      ).rejects.toThrow()
    })
  })
})
