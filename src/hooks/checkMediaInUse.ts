import type { CollectionBeforeDeleteHook } from 'payload'
import { APIError } from 'payload'

/**
 * Blocks deleting a Media document that is still referenced by a Post or
 * Project (cover, project gallery, or SEO share image), per
 * docs/CONTENT_MODEL.md: "Deletion of an in-use asset must be blocked
 * with readable references ... or require unlinking first."
 */
export const checkMediaInUse: CollectionBeforeDeleteHook = async ({ id, req }) => {
  const [postsByCover, postsBySeoImage, projectsByCover, projectsByGallery, projectsBySeoImage] =
    await Promise.all([
      req.payload.find({
        collection: 'posts',
        overrideAccess: true,
        limit: 5,
        where: { cover: { equals: id } },
      }),
      req.payload.find({
        collection: 'posts',
        overrideAccess: true,
        limit: 5,
        where: { 'seo.shareImage': { equals: id } },
      }),
      req.payload.find({
        collection: 'projects',
        overrideAccess: true,
        limit: 5,
        where: { cover: { equals: id } },
      }),
      req.payload.find({
        collection: 'projects',
        overrideAccess: true,
        limit: 5,
        where: { 'gallery.media': { equals: id } },
      }),
      req.payload.find({
        collection: 'projects',
        overrideAccess: true,
        limit: 5,
        where: { 'seo.shareImage': { equals: id } },
      }),
    ])

  const references = [
    ...postsByCover.docs.map((doc) => `post "${doc.title}" (cover)`),
    ...postsBySeoImage.docs.map((doc) => `post "${doc.title}" (SEO share image)`),
    ...projectsByCover.docs.map((doc) => `project "${doc.title}" (cover)`),
    ...projectsByGallery.docs.map((doc) => `project "${doc.title}" (gallery)`),
    ...projectsBySeoImage.docs.map((doc) => `project "${doc.title}" (SEO share image)`),
  ]

  if (references.length > 0) {
    throw new APIError(
      `This file is still in use and cannot be deleted. Unlink it first from: ${references.join(', ')}.`,
      409,
      null,
      true,
    )
  }
}
