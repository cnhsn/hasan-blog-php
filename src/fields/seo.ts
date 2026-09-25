import type { Field } from 'payload'

/** Shared SEO group for Posts and Projects (docs/CONTENT_MODEL.md). */
export function seoField(): Field {
  return {
    name: 'seo',
    type: 'group',
    fields: [
      {
        name: 'title',
        type: 'text',
        maxLength: 70,
      },
      {
        name: 'description',
        type: 'textarea',
        maxLength: 170,
      },
      {
        name: 'shareImage',
        type: 'upload',
        relationTo: 'media',
      },
    ],
  }
}
