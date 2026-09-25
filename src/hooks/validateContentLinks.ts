import type { CollectionBeforeValidateHook } from 'payload'
import { ValidationError } from 'payload'

import { findUnsafeLinks } from '@/lib/lexical'

/**
 * Rejects rich text links using an unsafe scheme (e.g. `javascript:`) at
 * save time, in addition to the frontend renderer's own defense-in-depth
 * check (src/components/richtext/converters.tsx).
 */
export function createLinkValidationHook(...fieldNames: string[]): CollectionBeforeValidateHook {
  return ({ data }) => {
    if (!data) return data

    const errors = fieldNames.flatMap((fieldName) => {
      const unsafe = findUnsafeLinks(data[fieldName])
      if (unsafe.length === 0) return []
      return [
        {
          path: fieldName,
          message: `Unsafe link scheme(s) not allowed: ${unsafe.join(', ')}`,
        },
      ]
    })

    if (errors.length > 0) {
      throw new ValidationError({ errors })
    }

    return data
  }
}
