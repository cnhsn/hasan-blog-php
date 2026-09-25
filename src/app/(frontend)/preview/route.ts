import { draftMode } from 'next/headers'
import { redirect } from 'next/navigation'

import { getOwnerUser } from '@/lib/auth'
import { type PreviewCollection, verifyPreviewToken } from '@/lib/preview'

export const dynamic = 'force-dynamic'

/** Only these collections have a public detail route to preview into. */
const DESTINATIONS: Record<PreviewCollection, string> = {
  posts: '/blog',
  projects: '/projects',
}

const SLUG_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

function isPreviewCollection(value: string): value is PreviewCollection {
  return value === 'posts' || value === 'projects'
}

function deny(message: string, status: number): Response {
  return new Response(message, {
    status,
    headers: { 'Cache-Control': 'private, no-store', 'Content-Type': 'text/plain; charset=utf-8' },
  })
}

/**
 * Draft preview entry point. Requires BOTH an authenticated owner
 * session AND a valid, unexpired signed token for this exact document —
 * a leaked token alone is not an indefinite public draft link. The
 * redirect target is derived from a fixed allowlist, never from
 * caller-supplied paths, so this cannot become an open redirect
 * (docs/SECURITY.md "Draft isolation").
 */
export async function GET(request: Request) {
  const url = new URL(request.url)
  const collection = url.searchParams.get('collection')
  const slug = url.searchParams.get('slug')
  const token = url.searchParams.get('token')

  if (!collection || !slug || !token) {
    return deny('Missing preview parameters.', 400)
  }
  if (!isPreviewCollection(collection)) {
    return deny('Unknown preview collection.', 400)
  }
  if (!SLUG_PATTERN.test(slug) || slug.length > 120) {
    return deny('Invalid preview slug.', 400)
  }

  const user = await getOwnerUser()
  if (!user) {
    return deny('Preview requires an authenticated owner session.', 401)
  }

  if (!verifyPreviewToken(token, { collection, slug })) {
    return deny('Invalid or expired preview token.', 401)
  }

  const draft = await draftMode()
  draft.enable()

  redirect(`${DESTINATIONS[collection]}/${slug}`)
}
