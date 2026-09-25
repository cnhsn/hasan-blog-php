import { draftMode } from 'next/headers'
import { redirect } from 'next/navigation'

export const dynamic = 'force-dynamic'

/**
 * Clears draft-preview state. The `to` parameter is only honoured when
 * it is a single-slash internal path, so it cannot be used as an open
 * redirect; anything else falls back to the homepage.
 */
export async function GET(request: Request) {
  const draft = await draftMode()
  draft.disable()

  const to = new URL(request.url).searchParams.get('to')
  const isInternalPath = Boolean(to && to.startsWith('/') && !to.startsWith('//') && !to.includes('://'))

  redirect(isInternalPath && to ? to : '/')
}
