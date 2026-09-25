import type { CollectionBeforeValidateHook } from 'payload'

import { slugify } from '@/lib/slug'

/**
 * Auto-generates `slug` from `titleField` the first time a document is
 * created, but never overwrites a slug the owner already set/edited.
 */
export function formatSlug(titleField = 'title'): CollectionBeforeValidateHook {
  return ({ data }) => {
    if (!data) return data

    const currentSlug = data.slug
    if (typeof currentSlug === 'string' && currentSlug.trim() !== '') {
      data.slug = currentSlug.trim()
      return data
    }

    const title = data[titleField]
    if (typeof title === 'string' && title.trim() !== '') {
      data.slug = slugify(title)
    }

    return data
  }
}
