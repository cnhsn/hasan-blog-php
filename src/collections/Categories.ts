import type { CollectionConfig } from 'payload'

import { isOwner } from '@/access'
import { formatSlug } from '@/hooks/formatSlug'
import { validateSlug } from '@/lib/slug'

export const Categories: CollectionConfig = {
  slug: 'categories',
  admin: {
    useAsTitle: 'name',
    defaultColumns: ['name', 'slug'],
  },
  access: {
    // Only safe name/slug metadata is ever exposed; see fields below.
    read: () => true,
    create: isOwner,
    update: isOwner,
    // Deleting a taxonomy must never cascade-delete posts; Payload leaves
    // the dangling relationship id in place and the frontend filters out
    // unpopulated relations when rendering, so plain owner-gated delete
    // (not a hard `denyDelete`) is safe here.
    delete: isOwner,
  },
  fields: [
    {
      name: 'name',
      type: 'text',
      required: true,
      unique: true,
      minLength: 1,
      maxLength: 80,
    },
    {
      name: 'slug',
      type: 'text',
      required: true,
      unique: true,
      index: true,
      validate: validateSlug,
      admin: {
        position: 'sidebar',
      },
    },
  ],
  hooks: {
    beforeValidate: [formatSlug('name')],
  },
}
