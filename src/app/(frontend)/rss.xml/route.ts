import { getEnv } from '@/lib/env'
import { getPayloadClient } from '@/lib/payload'
import { getSiteSettings } from '@/lib/queries/siteSettings'
import { escapeXml } from '@/lib/xml'

export const dynamic = 'force-dynamic'

export async function GET() {
  const env = getEnv()
  const payload = await getPayloadClient()
  const settings = await getSiteSettings()

  const result = await payload.find({
    collection: 'posts',
    overrideAccess: false,
    limit: 20,
    sort: '-publishedAt',
    depth: 0,
  })

  const items = result.docs
    .map((post) => {
      const link = `${env.publicSiteUrl}/blog/${post.slug}`
      const pubDate = post.publishedAt ? new Date(post.publishedAt).toUTCString() : null
      return `<item>
<title>${escapeXml(post.title)}</title>
<link>${escapeXml(link)}</link>
<guid isPermaLink="true">${escapeXml(link)}</guid>
${pubDate ? `<pubDate>${pubDate}</pubDate>` : ''}
<description>${escapeXml(post.excerpt)}</description>
</item>`
    })
    .join('\n')

  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
<title>${escapeXml(settings.siteTitle)}</title>
<link>${escapeXml(env.publicSiteUrl)}</link>
<description>${escapeXml(settings.shortDescription)}</description>
${items}
</channel>
</rss>`

  return new Response(xml, {
    headers: {
      'Content-Type': 'application/rss+xml; charset=utf-8',
      'Cache-Control': 'no-store',
    },
  })
}
