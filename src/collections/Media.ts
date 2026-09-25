import type { CollectionConfig } from 'payload'

import { isOwner, publicOrOwnerMedia } from '@/access'
import { checkMediaInUse } from '@/hooks/checkMediaInUse'
import { validateImageDimensions } from '@/hooks/validateImageDimensions'

export const Media: CollectionConfig = {
  slug: 'media',
  admin: {
    useAsTitle: 'filename',
    defaultColumns: ['filename', 'visibility', 'alt', 'updatedAt'],
  },
  access: {
    create: isOwner,
    read: publicOrOwnerMedia,
    update: isOwner,
    delete: isOwner,
  },
  upload: {
    // S3-only (see payload.config.ts s3Storage plugin); never fall back
    // to ephemeral app disk as the media store.
    disableLocalStorage: true,
    mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/avif'],
    // Never accept arbitrary external fetch URLs for image import.
    pasteURL: false,
    focalPoint: true,
    imageSizes: [
      { name: 'thumbnail', width: 400, height: undefined, withoutEnlargement: true },
      { name: 'medium', width: 800, height: undefined, withoutEnlargement: true },
      { name: 'large', width: 1600, height: undefined, withoutEnlargement: true },
    ],
  },
  fields: [
    {
      name: 'alt',
      type: 'text',
      required: true,
      minLength: 1,
      maxLength: 200,
      admin: {
        description: 'Required, meaningful alt text describing the image content.',
      },
    },
    {
      name: 'caption',
      type: 'text',
      maxLength: 300,
    },
    {
      name: 'visibility',
      type: 'select',
      required: true,
      defaultValue: 'private',
      options: [
        { label: 'Private (owner only)', value: 'private' },
        { label: 'Public', value: 'public' },
      ],
      admin: {
        position: 'sidebar',
        description:
          'Publishing a post/project requires every referenced asset to be public first. Making an asset private again does not retroactively hide it from caches that already fetched it (no-store delivery is used to minimize this).',
      },
    },
  ],
  hooks: {
    beforeValidate: [validateImageDimensions],
    beforeDelete: [checkMediaInUse],
  },
}
