import { getPayloadClient } from '@/lib/payload'
import { DEFAULT_SITE_SETTINGS } from '@/lib/siteSettingsDefaults'
import type { SiteSetting } from '@/payload-types'

function isNonEmpty(value: unknown): boolean {
  if (value === null || value === undefined) return false
  if (typeof value === 'string') return value.trim() !== ''
  if (Array.isArray(value)) return value.length > 0
  return true
}

/**
 * Public read of the SiteSettings global with defaults merged
 * underneath, so every consumer gets complete values even before the
 * global has been saved for the first time.
 */
export async function getSiteSettings(): Promise<SiteSetting> {
  const payload = await getPayloadClient()
  // Public read: explicit overrideAccess: false, no user context.
  const stored = await payload.findGlobal({
    slug: 'site-settings',
    overrideAccess: false,
  })

  return {
    ...stored,
    siteTitle: isNonEmpty(stored.siteTitle) ? stored.siteTitle : DEFAULT_SITE_SETTINGS.siteTitle,
    shortDescription: isNonEmpty(stored.shortDescription)
      ? stored.shortDescription
      : DEFAULT_SITE_SETTINGS.shortDescription,
    authorDisplayName: isNonEmpty(stored.authorDisplayName)
      ? stored.authorDisplayName
      : DEFAULT_SITE_SETTINGS.authorDisplayName,
    heroGreeting: isNonEmpty(stored.heroGreeting)
      ? stored.heroGreeting
      : DEFAULT_SITE_SETTINGS.heroGreeting,
    introHeading: isNonEmpty(stored.introHeading)
      ? stored.introHeading
      : DEFAULT_SITE_SETTINGS.introHeading,
    introBody: isNonEmpty(stored.introBody) ? stored.introBody : DEFAULT_SITE_SETTINGS.introBody,
    interests: isNonEmpty(stored.interests) ? stored.interests : DEFAULT_SITE_SETTINGS.interests,
    copyrightName: isNonEmpty(stored.copyrightName)
      ? stored.copyrightName
      : DEFAULT_SITE_SETTINGS.copyrightName,
    defaultSeoTitle: isNonEmpty(stored.defaultSeoTitle)
      ? stored.defaultSeoTitle
      : DEFAULT_SITE_SETTINGS.defaultSeoTitle,
    defaultSeoDescription: isNonEmpty(stored.defaultSeoDescription)
      ? stored.defaultSeoDescription
      : DEFAULT_SITE_SETTINGS.defaultSeoDescription,
    nav: {
      homeLabel: isNonEmpty(stored.nav?.homeLabel)
        ? stored.nav.homeLabel
        : DEFAULT_SITE_SETTINGS.nav.homeLabel,
      blogLabel: isNonEmpty(stored.nav?.blogLabel)
        ? stored.nav.blogLabel
        : DEFAULT_SITE_SETTINGS.nav.blogLabel,
      projectsLabel: isNonEmpty(stored.nav?.projectsLabel)
        ? stored.nav.projectsLabel
        : DEFAULT_SITE_SETTINGS.nav.projectsLabel,
    },
  }
}
