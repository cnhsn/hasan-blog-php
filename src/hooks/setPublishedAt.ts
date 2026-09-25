import type { CollectionBeforeChangeHook } from 'payload'

/**
 * `publishedAt` is set once, at the first actual publication, and never
 * moved afterwards by further edits (docs/CONTENT_MODEL.md). Stored in
 * UTC (Payload's date field default).
 */
export const setPublishedAt: CollectionBeforeChangeHook = ({ data, originalDoc }) => {
  if (originalDoc?.publishedAt) {
    data.publishedAt = originalDoc.publishedAt
  } else if (data._status === 'published' && !data.publishedAt) {
    data.publishedAt = new Date().toISOString()
  }
  return data
}
