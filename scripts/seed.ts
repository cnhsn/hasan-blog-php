/**
 * Idempotent default SiteSettings seed only — no fabricated posts or
 * projects (docs/PROJECT_SPEC.md: "Initial public post/project
 * collections are empty"). Values live in src/lib/siteSettingsDefaults.ts
 * so the seed and the runtime fallback cannot drift apart.
 */
import { getPayload } from 'payload'

import { DEFAULT_SITE_SETTINGS } from '../src/lib/siteSettingsDefaults.ts'
import config from '../src/payload.config.ts'

async function main() {
  const payload = await getPayload({ config })

  await payload.updateGlobal({
    slug: 'site-settings',
    data: DEFAULT_SITE_SETTINGS,
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
