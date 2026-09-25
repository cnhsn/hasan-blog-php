import { headers as nextHeaders } from 'next/headers'
import type { TypedUser } from 'payload'

import { getPayloadClient } from './payload'

/**
 * Resolves the Payload owner session from the incoming request cookies.
 * Used by the preview entry route and re-checked on every draft render,
 * so an expired/logged-out session cannot keep serving drafts even if
 * the Next.js draft-mode cookie is still present (docs/SECURITY.md).
 */
export async function getOwnerUser(): Promise<TypedUser | null> {
  const payload = await getPayloadClient()
  const requestHeaders = await nextHeaders()
  const { user } = await payload.auth({ headers: requestHeaders })
  return user ?? null
}
