import './globals.css'

import { Footer } from '@/components/layout/Footer'
import { Header } from '@/components/layout/Header'
import { RouteFocusManager } from '@/components/motion/RouteFocusManager'
import { getEnv } from '@/lib/env'
import { getSiteSettings } from '@/lib/queries/siteSettings'
import type { Metadata } from 'next'

/**
 * Everything under the public route group renders per request. Public
 * pages read the CMS directly (docs/DECISIONS.md "start uncached"), so
 * prerendering would both bake stale content into the build and make
 * the build depend on a reachable database — which docs/OPERATIONS.md
 * explicitly rules out. Applies to nested segments, including the
 * not-found page.
 */
export const dynamic = 'force-dynamic'

export async function generateMetadata(): Promise<Metadata> {
  const settings = await getSiteSettings()
  const env = getEnv()

  return {
    metadataBase: new URL(env.publicSiteUrl),
    title: {
      default: settings.siteTitle,
      template: `%s — ${settings.authorDisplayName}`,
    },
    description: settings.shortDescription,
  }
}

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const settings = await getSiteSettings()

  const navItems = [
    { href: '/', label: settings.nav.homeLabel },
    { href: '/blog', label: settings.nav.blogLabel },
    { href: '/projects', label: settings.nav.projectsLabel },
  ]

  return (
    <html lang="en">
      <body>
        <div className="site-shell">
          <Header items={navItems} />
          {children}
          <Footer
            copyrightName={settings.copyrightName}
            socialLinks={settings.socialLinks ?? []}
          />
        </div>
        <RouteFocusManager />
      </body>
    </html>
  )
}
