import type { Media } from '@/payload-types'

/**
 * Renders CMS media using the responsive variants Payload already
 * generated (see Media.upload.imageSizes), rather than next/image.
 *
 * Two reasons:
 *  - Payload returns absolute URLs built from `serverURL`, which
 *    next/image rejects unless every deployment hostname is listed in
 *    `images.remotePatterns`. Normalising to a same-origin path avoids
 *    that per-environment configuration entirely.
 *  - The Next image optimizer would re-encode images that Payload has
 *    already resized, and it fetches them server-side without the
 *    viewer's cookies — which breaks owner previews of not-yet-public
 *    assets.
 *
 * Width/height are always emitted so the browser reserves the correct
 * aspect ratio before the image loads.
 */

type MediaSize = {
  url?: null | string
  width?: null | number
}

function toSameOriginPath(url: null | string | undefined): null | string {
  if (!url) return null
  if (url.startsWith('/')) return url
  try {
    const parsed = new URL(url)
    return `${parsed.pathname}${parsed.search}`
  } catch {
    return null
  }
}

function buildSrcSet(media: Media): string | undefined {
  const variants = Object.values(media.sizes ?? {}) as MediaSize[]
  const entries = variants
    .map((variant) => {
      const path = toSameOriginPath(variant?.url)
      return path && variant?.width ? `${path} ${variant.width}w` : null
    })
    .filter((entry): entry is string => entry !== null)

  const originalPath = toSameOriginPath(media.url)
  if (originalPath && media.width) {
    entries.push(`${originalPath} ${media.width}w`)
  }

  return entries.length > 0 ? entries.join(', ') : undefined
}

export function MediaImage({
  media,
  sizes,
  priority = false,
}: {
  media: Media
  /** The `sizes` attribute describing the rendered width at each breakpoint. */
  sizes: string
  priority?: boolean
}) {
  const src = toSameOriginPath(media.url)
  if (!src) return null

  return (
    // eslint-disable-next-line @next/next/no-img-element -- see component docblock
    <img
      src={src}
      srcSet={buildSrcSet(media)}
      sizes={sizes}
      alt={media.alt}
      width={media.width ?? undefined}
      height={media.height ?? undefined}
      loading={priority ? 'eager' : 'lazy'}
      decoding="async"
      fetchPriority={priority ? 'high' : undefined}
    />
  )
}
