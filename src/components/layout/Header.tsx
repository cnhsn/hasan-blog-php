'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'

type NavItem = {
  href: string
  label: string
}

export function Header({ items }: { items: NavItem[] }) {
  const pathname = usePathname()

  return (
    <header className="site-header">
      <Link className="brand" href="/" aria-label="Hasan home">
        hasan<span>.</span>
      </Link>
      <nav className="site-nav" aria-label="Main navigation">
        {items.map((item) => {
          const isActive =
            item.href === '/' ? pathname === '/' : pathname.startsWith(item.href)
          return (
            <Link key={item.href} href={item.href} aria-current={isActive ? 'page' : undefined}>
              {item.label}
            </Link>
          )
        })}
      </nav>
    </header>
  )
}
