import type { CollectionAfterChangeHook, CollectionAfterDeleteHook, PayloadRequest } from 'payload'

/**
 * Maintains the automatic slug-history side of the `redirects` collection
 * (see docs/CONTENT_MODEL.md "Redirects"). Runs only as a side effect of
 * an already-authorized owner write to Posts/Projects (never in response
 * to an unauthenticated request), so the internal `overrideAccess: true`
 * bookkeeping calls below are safe: they write to a non-sensitive
 * path-mapping table, not user content, and only ever run after Payload's
 * own access control already approved the triggering Post/Project change.
 */

const MAX_FLATTEN_HOPS = 10

async function flattenAndUpsertRedirect(
  req: PayloadRequest,
  sourcePath: string,
  targetPath: string,
): Promise<void> {
  if (sourcePath === targetPath) return

  let finalTarget = targetPath
  for (let hop = 0; hop < MAX_FLATTEN_HOPS; hop += 1) {
    const next = await req.payload.find({
      collection: 'redirects',
      limit: 1,
      overrideAccess: true,
      where: { sourcePath: { equals: finalTarget } },
    })
    const nextTarget = next.docs[0]?.targetPath
    if (typeof nextTarget === 'string' && nextTarget !== finalTarget) {
      finalTarget = nextTarget
    } else {
      break
    }
  }

  // Refuse to create a self-referencing loop.
  if (finalTarget === sourcePath) return

  // Flatten any existing redirects that pointed at the path we're now
  // moving away from, so nothing chains through a stale hop.
  const incoming = await req.payload.find({
    collection: 'redirects',
    limit: 100,
    overrideAccess: true,
    where: { targetPath: { equals: sourcePath } },
  })
  for (const doc of incoming.docs) {
    if (doc.sourcePath !== finalTarget) {
      await req.payload.update({
        id: doc.id,
        collection: 'redirects',
        data: { targetPath: finalTarget },
        overrideAccess: true,
      })
    }
  }

  const existing = await req.payload.find({
    collection: 'redirects',
    limit: 1,
    overrideAccess: true,
    where: { sourcePath: { equals: sourcePath } },
  })

  if (existing.docs[0]) {
    await req.payload.update({
      id: existing.docs[0].id,
      collection: 'redirects',
      data: { targetPath: finalTarget },
      overrideAccess: true,
    })
  } else {
    await req.payload.create({
      collection: 'redirects',
      data: { sourcePath, targetPath: finalTarget },
      overrideAccess: true,
    })
  }
}

async function deactivateRedirectsTo(req: PayloadRequest, path: string): Promise<void> {
  const pointing = await req.payload.find({
    collection: 'redirects',
    limit: 100,
    overrideAccess: true,
    where: { targetPath: { equals: path } },
  })
  for (const doc of pointing.docs) {
    await req.payload.delete({ id: doc.id, collection: 'redirects', overrideAccess: true })
  }
}

export function createSlugHistoryHooks(pathPrefix: '/blog' | '/projects'): {
  afterChange: CollectionAfterChangeHook
  afterDelete: CollectionAfterDeleteHook
} {
  const afterChange: CollectionAfterChangeHook = async ({ doc, operation, previousDoc, req }) => {
    if (operation !== 'update' || !previousDoc) return doc

    const oldSlug = typeof previousDoc.slug === 'string' ? previousDoc.slug : undefined
    const newSlug = typeof doc.slug === 'string' ? doc.slug : undefined
    const wasPublished = previousDoc._status === 'published'
    const isPublished = doc._status === 'published'

    if (oldSlug && newSlug && oldSlug !== newSlug && wasPublished) {
      await flattenAndUpsertRedirect(req, `${pathPrefix}/${oldSlug}`, `${pathPrefix}/${newSlug}`)
    }

    if (wasPublished && !isPublished && newSlug) {
      await deactivateRedirectsTo(req, `${pathPrefix}/${newSlug}`)
    }

    return doc
  }

  const afterDelete: CollectionAfterDeleteHook = async ({ doc, req }) => {
    const slug = typeof doc.slug === 'string' ? doc.slug : undefined
    if (slug) {
      await deactivateRedirectsTo(req, `${pathPrefix}/${slug}`)
    }
    return doc
  }

  return { afterChange, afterDelete }
}
