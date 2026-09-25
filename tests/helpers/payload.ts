import { getPayload, type Payload, type TypedUser } from 'payload'
import sharp from 'sharp'

import config from '@/payload.config'

let cached: Promise<Payload> | undefined

export function getTestPayload(): Promise<Payload> {
  if (!cached) {
    cached = getPayload({ config })
  }
  return cached
}

const OWNER_EMAIL = 'test-owner@example.invalid'
const OWNER_PASSWORD = 'integration-test-password'

/**
 * Creates (once) the single owner used by integration tests. Goes
 * through the same guarded path as `pnpm admin:bootstrap`: the
 * `isBootstrapCli` context is the only way past
 * src/hooks/restrictUserCreation.ts.
 */
export async function getTestOwner(): Promise<TypedUser> {
  const payload = await getTestPayload()

  const existing = await payload.find({
    collection: 'users',
    overrideAccess: true,
    limit: 1,
    where: { email: { equals: OWNER_EMAIL } },
  })

  if (existing.docs[0]) return existing.docs[0] as TypedUser

  const created = await payload.create({
    collection: 'users',
    context: { isBootstrapCli: true },
    data: {
      email: OWNER_EMAIL,
      password: OWNER_PASSWORD,
      displayName: 'Test Owner',
      role: 'owner',
    },
    overrideAccess: true,
  })

  return created as TypedUser
}

/** Deletes all content so list/pagination assertions start from a known state. */
export async function resetContent(): Promise<void> {
  const payload = await getTestPayload()
  for (const collection of ['posts', 'projects', 'redirects', 'media', 'categories', 'tags'] as const) {
    await payload.delete({
      collection,
      overrideAccess: true,
      where: { id: { exists: true } },
    })
  }
}

/** Minimal valid Lexical document containing a single paragraph. */
export function lexicalParagraph(text: string) {
  return {
    root: {
      type: 'root',
      direction: 'ltr' as const,
      format: '' as const,
      indent: 0,
      version: 1,
      children: [
        {
          type: 'paragraph',
          direction: 'ltr' as const,
          format: '' as const,
          indent: 0,
          version: 1,
          textFormat: 0,
          children: [
            {
              type: 'text',
              detail: 0,
              format: 0,
              mode: 'normal',
              style: '',
              text,
              version: 1,
            },
          ],
        },
      ],
    },
  }
}

/** Generates a real, decodable PNG so upload validation runs for real. */
export async function pngBuffer(width = 32, height = 32): Promise<Buffer> {
  return sharp({
    create: {
      width,
      height,
      channels: 3,
      background: { r: 20, g: 42, b: 238 },
    },
  })
    .png()
    .toBuffer()
}
