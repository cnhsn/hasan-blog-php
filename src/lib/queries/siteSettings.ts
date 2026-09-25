import { getPayloadClient } from '@/lib/payload'
import type { SiteSetting } from '@/payload-types'

export async function getSiteSettings(): Promise<SiteSetting> {
  const payload = await getPayloadClient()
  // Public read: explicit overrideAccess: false, no user context.
  return payload.findGlobal({
    slug: 'site-settings',
    overrideAccess: false,
  })
}
