import { notFound } from 'next/navigation'

/**
 * Routes unmatched URLs into the public layout so they get the branded
 * 404 page with real navigation, instead of Next's bare default. More
 * specific routes (including /admin and /api) still win over this
 * catch-all.
 */
export default function NotFoundCatchAll(): never {
  notFound()
}
