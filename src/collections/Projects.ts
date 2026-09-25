import type { CollectionConfig } from 'payload'

import { isOwner, publishedOrOwner } from '@/access'
import { seoField } from '@/fields/seo'
import { formatSlug } from '@/hooks/formatSlug'
import { validateProjectMediaPublic } from '@/hooks/publishValidation'
import { setPublishedAt } from '@/hooks/setPublishedAt'
import { createSlugHistoryHooks } from '@/hooks/slugHistory'
import { createLinkValidationHook } from '@/hooks/validateContentLinks'
import { createRichTextEditor } from '@/lib/editor'
import { createPreviewToken } from '@/lib/preview'
import { validateSlug } from '@/lib/slug'
import { validateHttpUrl } from '@/lib/url'

const { afterChange: slugHistoryAfterChange, afterDelete: slugHistoryAfterDelete } =
  createSlugHistoryHooks('/projects')

export const Projects: CollectionConfig = {
  slug: 'projects',
  admin: {
    useAsTitle: 'title',
    defaultColumns: ['title', 'slug', '_status', 'lifecycleStatus', 'featured'],
    preview: (doc) => {
      const slug = typeof doc.slug === 'string' ? doc.slug : ''
      if (!slug) return null
      const token = createPreviewToken({ collection: 'projects', slug })
      return `/preview?collection=projects&slug=${encodeURIComponent(slug)}&token=${token}`
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
      name: 'shortDescription',
      type: 'textarea',
      required: true,
      maxLength: 320,
    },
    {
      name: 'caseStudy',
      type: 'richText',
      required: true,
      editor: createRichTextEditor(),
    },
    {
      name: 'problem',
      type: 'richText',
      editor: createRichTextEditor(),
      admin: {
        description: 'Optional structured section.',
      },
    },
    {
      name: 'approach',
      type: 'richText',
      editor: createRichTextEditor(),
      admin: {
        description: 'Optional structured section.',
      },
    },
    {
      name: 'results',
      type: 'richText',
      editor: createRichTextEditor(),
      admin: {
        description: 'Optional structured section. Avoid fabricated outcome metrics.',
      },
    },
    {
      name: 'cover',
      type: 'upload',
      relationTo: 'media',
    },
    {
      name: 'gallery',
      type: 'array',
      maxRows: 12,
      fields: [
        {
          name: 'media',
          type: 'upload',
          relationTo: 'media',
          required: true,
        },
        {
          name: 'caption',
          type: 'text',
          maxLength: 300,
        },
      ],
    },
    {
      name: 'technologies',
      type: 'text',
      hasMany: true,
      maxRows: 12,
    },
    {
      name: 'repositoryURL',
      type: 'text',
      validate: validateHttpUrl,
    },
    {
      name: 'liveURL',
      type: 'text',
      validate: validateHttpUrl,
    },
    {
      name: 'lifecycleStatus',
      type: 'select',
      required: true,
      defaultValue: 'idea',
      options: [
        { label: 'Idea', value: 'idea' },
        { label: 'In progress', value: 'in-progress' },
        { label: 'Completed', value: 'completed' },
        { label: 'Archived', value: 'archived' },
      ],
      admin: {
        position: 'sidebar',
        description: 'Independent of the draft/published status above.',
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
    {
      name: 'startDate',
      type: 'date',
      required: true,
    },
    {
      name: 'endDate',
      type: 'date',
      validate: (value, { siblingData }) => {
        if (!value) return true
        const start = (siblingData as { startDate?: Date | string } | undefined)?.startDate
        if (start && new Date(value).getTime() < new Date(start).getTime()) {
          return 'End date cannot precede start date.'
        }
        return true
      },
    },
    seoField(),
  ],
  hooks: {
    beforeValidate: [
      formatSlug('title'),
      createLinkValidationHook('caseStudy', 'problem', 'approach', 'results'),
    ],
    beforeChange: [setPublishedAt, validateProjectMediaPublic],
    afterChange: [slugHistoryAfterChange],
    afterDelete: [slugHistoryAfterDelete],
  },
}
