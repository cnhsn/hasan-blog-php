import type { MetadataRoute } from 'next'

import { getEnv } from '@/lib/env'

export const dynamic = 'force-dynamic'

export default function robots(): MetadataRoute.Robots {
  const env = getEnv()
  return {
    rules: {
      userAgent: '*',
      allow: '/',
      // Crawl-policy hint only, not an access-control mechanism
      // (docs/PROJECT_SPEC.md) — real protection is auth + access control.
      disallow: ['/admin', '/api', '/preview'],
    },
    sitemap: `${env.publicSiteUrl}/sitemap.xml`,
  }
}
