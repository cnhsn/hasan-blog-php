'use client'

import { usePathname } from 'next/navigation'
import { useEffect, useRef } from 'react'

/**
 * Moves focus to the new page's main heading after a client-side route
 * change (not on first load) so keyboard/assistive-tech users land
 * somewhere meaningful instead of on a stale focus target
 * (docs/DESIGN_AND_MOTION.md).
 */
export function RouteFocusManager() {
  const pathname = usePathname()
  const hasMounted = useRef(false)

  useEffect(() => {
    if (!hasMounted.current) {
      hasMounted.current = true
      return
    }
    const heading = document.querySelector<HTMLElement>('main h1')
    if (!heading) return
    if (!heading.hasAttribute('tabindex')) {
      heading.setAttribute('tabindex', '-1')
    }
    heading.focus()
  }, [pathname])

  return null
}
