import type { CollectionBeforeChangeHook, PayloadRequest } from 'payload'
import { APIError } from 'payload'

function toId(value: unknown): number | string | null {
  if (typeof value === 'string' || typeof value === 'number') return value
  if (value && typeof value === 'object' && 'id' in value) {
    const id = (value as { id: unknown }).id
    if (typeof id === 'string' || typeof id === 'number') return id
  }
  return null
}

/**
 * Publish validation rule from docs/CONTENT_MODEL.md: "publication
 * validation requires all referenced media to be public, with clear
 * admin error." Runs only when transitioning to `_status: 'published'`.
 */
async function assertMediaPublic(req: PayloadRequest, ids: Array<number | string | null>): Promise<void> {
  const uniqueIds = [...new Set(ids.filter((id): id is number | string => id !== null))]
  if (uniqueIds.length === 0) return

  const result = await req.payload.find({
    collection: 'media',
    overrideAccess: true,
    limit: uniqueIds.length,
    where: { id: { in: uniqueIds } },
  })

  const nonPublic = result.docs.filter((doc) => doc.visibility !== 'public')
  if (nonPublic.length > 0) {
    const names = nonPublic.map((doc) => doc.filename ?? doc.id).join(', ')
    throw new APIError(
      `Cannot publish: the following referenced media must be marked public first: ${names}.`,
      400,
      null,
      true,
    )
  }
}

export const validatePostMediaPublic: CollectionBeforeChangeHook = async ({ data, req }) => {
  if (data._status !== 'published') return data
  await assertMediaPublic(req, [toId(data.cover), toId(data.seo?.shareImage)])
  return data
}

export const validateProjectMediaPublic: CollectionBeforeChangeHook = async ({ data, req }) => {
  if (data._status !== 'published') return data
  const galleryIds = Array.isArray(data.gallery)
    ? data.gallery.map((item: unknown) =>
        toId((item as { media?: unknown } | undefined)?.media),
      )
    : []
  await assertMediaPublic(req, [toId(data.cover), toId(data.seo?.shareImage), ...galleryIds])
  return data
}
