import type { GlobalConfig } from 'payload'

import { isOwner } from '@/access'
import { validateHttpsUrl } from '@/lib/url'

/**
 * Seeded from reference-site/index.html by scripts/seed.mjs (see
 * docs/CONTENT_MODEL.md "SiteSettings"). No invented social links.
 */
export const SiteSettings: GlobalConfig = {
  slug: 'site-settings',
  access: {
    read: () => true,
    update: isOwner,
  },
  fields: [
    {
      name: 'siteTitle',
      type: 'text',
      required: true,
      maxLength: 100,
    },
    {
      name: 'shortDescription',
      type: 'textarea',
      required: true,
      maxLength: 300,
    },
    {
      name: 'authorDisplayName',
      type: 'text',
      required: true,
      defaultValue: 'Hasan',
      maxLength: 80,
    },
    {
      name: 'heroGreeting',
      type: 'text',
      required: true,
      maxLength: 80,
      admin: {
        description: 'Homepage headline, e.g. "Hello, I\'m Hasan."',
      },
    },
    {
      name: 'introHeading',
      type: 'text',
      required: true,
      maxLength: 120,
    },
    {
      name: 'introBody',
      type: 'textarea',
      required: true,
      maxLength: 400,
    },
    {
      name: 'interests',
      type: 'text',
      hasMany: true,
      maxRows: 5,
      admin: {
        description: 'Short interest labels shown as non-interactive tags on the homepage.',
      },
    },
    {
      name: 'copyrightName',
      type: 'text',
      required: true,
      defaultValue: 'Hasan',
      maxLength: 80,
    },
    {
      name: 'socialLinks',
      type: 'array',
      maxRows: 6,
      fields: [
        {
          name: 'label',
          type: 'text',
          required: true,
          maxLength: 40,
        },
        {
          name: 'url',
          type: 'text',
          required: true,
          validate: validateHttpsUrl,
        },
      ],
    },
    {
      name: 'defaultSeoTitle',
      type: 'text',
      required: true,
      maxLength: 70,
    },
    {
      name: 'defaultSeoDescription',
      type: 'textarea',
      required: true,
      maxLength: 170,
    },
    {
      name: 'nav',
      type: 'group',
      admin: {
        description: 'Labels only — internal destinations (/, /blog, /projects) are fixed.',
      },
      fields: [
        { name: 'homeLabel', type: 'text', required: true, defaultValue: 'Home', maxLength: 30 },
        { name: 'blogLabel', type: 'text', required: true, defaultValue: 'Blog', maxLength: 30 },
        {
          name: 'projectsLabel',
          type: 'text',
          required: true,
          defaultValue: 'Projects',
          maxLength: 30,
        },
      ],
    },
  ],
}
