import type { CollectionConfig } from 'payload'

import { isOwner } from '@/access'
import { formatSlug } from '@/hooks/formatSlug'
import { validateSlug } from '@/lib/slug'

export const Tags: CollectionConfig = {
  slug: 'tags',
  admin: {
    useAsTitle: 'name',
    defaultColumns: ['name', 'slug'],
  },
  access: {
    read: () => true,
    create: isOwner,
    update: isOwner,
    delete: isOwner,
  },
  fields: [
    {
      name: 'name',
      type: 'text',
      required: true,
      unique: true,
      minLength: 1,
      maxLength: 40,
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
