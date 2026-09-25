import type { SiteSetting } from '@/payload-types'

/**
 * Initial site settings, mirroring reference-site/index.html
 * (docs/CONTENT_MODEL.md: "Seed from reference-site/index.html; no
 * invented social links").
 *
 * Shared by `pnpm seed` and by the site-settings query, which merges
 * these underneath whatever the CMS returns. That keeps public routes
 * from crashing or rendering blanks before the global has ever been
 * saved — the RSS feed used to 500 on an unseeded database — and keeps
 * the seeded values and the fallback values from drifting apart.
 */
export const DEFAULT_SITE_SETTINGS = {
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
} satisfies Omit<SiteSetting, 'id' | 'createdAt' | 'updatedAt'>
