/**
 * Idempotent default SiteSettings seed only — no fabricated posts or
 * projects (docs/PROJECT_SPEC.md: "Initial public post/project
 * collections are empty"). Values live in src/lib/siteSettingsDefaults.ts
 * so the seed and the runtime fallback cannot drift apart.
 *
 * Registered as a Payload bin script (see `bin` in src/payload.config.ts)
 * so it runs through Payload's own TypeScript loader. That means the
 * identical command works in development and inside the production
 * container, which ships no devDependencies:
 *
 *   node node_modules/payload/bin.js seed
 */
import type { SanitizedConfig } from 'payload'
import { getPayload } from 'payload'

import { DEFAULT_SITE_SETTINGS } from '../src/lib/siteSettingsDefaults.ts'

export async function script(config: SanitizedConfig): Promise<void> {
  const payload = await getPayload({ config })

  await payload.updateGlobal({
    slug: 'site-settings',
    data: DEFAULT_SITE_SETTINGS,
    overrideAccess: true,
  })

  console.log('Seeded default site settings from reference-site/index.html.')
  process.exit(0)
}
