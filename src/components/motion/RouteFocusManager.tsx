'use client'

import { usePathname } from 'next/navigation'
import { useEffect, useRef } from 'react'

/**
 * Moves focus to the new page's main heading after a client-side route
 * change so keyboard and assistive-tech users land somewhere meaningful
 * (docs/DESIGN_AND_MOTION.md).
 *
 * Keyed on the previous pathname rather than a "first render" flag:
 * React StrictMode invokes effects twice on mount in development, which
 * made a simple flag focus the heading on initial page load and draw a
 * focus ring the user never asked for.
 */
export function RouteFocusManager() {
  const pathname = usePathname()
  const previousPathname = useRef<null | string>(null)

  useEffect(() => {
    if (previousPathname.current === null || previousPathname.current === pathname) {
      previousPathname.current = pathname
      return
    }
    previousPathname.current = pathname

    const heading = document.querySelector<HTMLElement>('main h1')
    if (!heading) return
    if (!heading.hasAttribute('tabindex')) {
      heading.setAttribute('tabindex', '-1')
    }
    heading.focus()
  }, [pathname])

  return null
}
