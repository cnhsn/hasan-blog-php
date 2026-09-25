import { createHmac, timingSafeEqual } from 'node:crypto'

import { getEnv } from './env'

/**
 * Short-lived signed token proving "the owner clicked Preview on this
 * exact document a few minutes ago" — not a standalone bearer of
 * access. The /preview route (src/app/(frontend)/preview/route.ts)
 * requires BOTH a valid, unexpired token AND an active owner session
 * before enabling Next.js draft mode, so a leaked token alone cannot
 * become an indefinite public draft link (docs/SECURITY.md).
 */
const TOKEN_TTL_SECONDS = 300

export type PreviewCollection = 'posts' | 'projects'

function sign(payload: string): string {
  return createHmac('sha256', getEnv().payloadSecret).update(payload).digest('base64url')
}

export function createPreviewToken(params: { collection: PreviewCollection; slug: string }): string {
  const exp = Math.floor(Date.now() / 1000) + TOKEN_TTL_SECONDS
  const payload = `${params.collection}:${params.slug}:${exp}`
  const signature = sign(payload)
  return `${Buffer.from(payload, 'utf8').toString('base64url')}.${signature}`
}

export function verifyPreviewToken(
  token: string,
  params: { collection: PreviewCollection; slug: string },
): boolean {
  const [payloadEncoded, signature] = token.split('.')
  if (!payloadEncoded || !signature) return false

  const payload = Buffer.from(payloadEncoded, 'base64url').toString('utf8')
  const expectedSignature = sign(payload)

  const signatureBuffer = Buffer.from(signature)
  const expectedBuffer = Buffer.from(expectedSignature)
  if (signatureBuffer.length !== expectedBuffer.length) return false
  if (!timingSafeEqual(signatureBuffer, expectedBuffer)) return false

  const [collection, slug, expString] = payload.split(':')
  const exp = Number(expString)
  if (!Number.isFinite(exp) || Date.now() / 1000 > exp) return false
  if (collection !== params.collection || slug !== params.slug) return false

  return true
}
