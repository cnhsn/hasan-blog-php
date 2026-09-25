import type { CollectionConfig } from 'payload'

import { isOwner, publishedOrOwner } from '@/access'
import { seoField } from '@/fields/seo'
import { formatSlug } from '@/hooks/formatSlug'
import { validatePostMediaPublic } from '@/hooks/publishValidation'
import { setPublishedAt } from '@/hooks/setPublishedAt'
import { createSlugHistoryHooks } from '@/hooks/slugHistory'
import { createLinkValidationHook } from '@/hooks/validateContentLinks'
import { createRichTextEditor } from '@/lib/editor'
import { createPreviewToken } from '@/lib/preview'
import { validateSlug } from '@/lib/slug'

const { afterChange: slugHistoryAfterChange, afterDelete: slugHistoryAfterDelete } =
  createSlugHistoryHooks('/blog')

export const Posts: CollectionConfig = {
  slug: 'posts',
  admin: {
    useAsTitle: 'title',
    defaultColumns: ['title', 'slug', '_status', 'publishedAt', 'featured'],
    preview: (doc) => {
      const slug = typeof doc.slug === 'string' ? doc.slug : ''
      if (!slug) return null
      const token = createPreviewToken({ collection: 'posts', slug })
      return `/preview?collection=posts&slug=${encodeURIComponent(slug)}&token=${token}`
    },
  },
  versions: {
    drafts: {
      autosave: {
        interval: 2000,
      },
    },
    maxPerDoc: 50,
  },
  access: {
    create: isOwner,
    read: publishedOrOwner,
    update: isOwner,
    delete: isOwner,
  },
  fields: [
    {
      name: 'title',
      type: 'text',
      required: true,
      minLength: 1,
      maxLength: 160,
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
    {
      name: 'excerpt',
      type: 'textarea',
      required: true,
      maxLength: 320,
    },
    {
      name: 'content',
      type: 'richText',
      required: true,
      editor: createRichTextEditor(),
    },
    {
      name: 'cover',
      type: 'upload',
      relationTo: 'media',
    },
    {
      name: 'authorName',
      type: 'text',
      required: true,
      defaultValue: 'Hasan',
      maxLength: 80,
    },
    {
      name: 'categories',
      type: 'relationship',
      relationTo: 'categories',
      hasMany: true,
    },
    {
      name: 'tags',
      type: 'relationship',
      relationTo: 'tags',
      hasMany: true,
      maxRows: 12,
    },
    {
      name: 'publishedAt',
      type: 'date',
      admin: {
        position: 'sidebar',
        readOnly: true,
        description: 'Set automatically on first publish; preserved on later edits.',
      },
    },
    {
      name: 'featured',
      type: 'checkbox',
      defaultValue: false,
      admin: {
        position: 'sidebar',
      },
    },
    seoField(),
  ],
  hooks: {
    beforeValidate: [formatSlug('title'), createLinkValidationHook('content')],
    beforeChange: [setPublishedAt, validatePostMediaPublic],
    afterChange: [slugHistoryAfterChange],
    afterDelete: [slugHistoryAfterDelete],
  },
}
