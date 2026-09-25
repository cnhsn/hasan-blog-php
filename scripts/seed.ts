/**
 * Idempotent default SiteSettings seed only — no fabricated posts or
 * projects (docs/PROJECT_SPEC.md: "Initial public post/project
 * collections are empty"). Content mirrors reference-site/index.html
 * verbatim; see docs/CONTENT_MODEL.md "SiteSettings".
 */
import { getPayload } from 'payload'

import config from '../src/payload.config.ts'

async function main() {
  const payload = await getPayload({ config })

  await payload.updateGlobal({
    slug: 'site-settings',
    data: {
      siteTitle: 'Hasan — A personal journal',
      shortDescription:
        "Hasan's personal corner of the internet. Notes on technology, things worth making, and life along the way.",
      authorDisplayName: 'Hasan',
      heroGreeting: "Hello, I'm Hasan.",
      introHeading: 'A curious mind. A few open tabs.',
      introBody:
        "I'm a Data & AI Engineer with an interest in how things work — and how to make them better. This is my space for technology, ideas, and life along the way.",
      interests: ['Data & AI', 'Building things', 'Everyday life'],
      copyrightName: 'Hasan',
      socialLinks: [],
      defaultSeoTitle: 'Hasan — A personal journal',
      defaultSeoDescription:
        "Hasan's personal corner of the internet. Notes on technology, things worth making, and life along the way.",
      nav: {
        homeLabel: 'Home',
        blogLabel: 'Blog',
        projectsLabel: 'Projects',
      },
    },
    overrideAccess: true,
  })

  console.log('Seeded default site settings from reference-site/index.html.')
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error('Seed failed:', error instanceof Error ? error.message : error)
    process.exit(1)
  })
