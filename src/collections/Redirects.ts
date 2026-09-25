import type { CollectionConfig } from 'payload'

import { isOwner } from '@/access'
import { validateInternalPath } from '@/lib/slug'

export const Redirects: CollectionConfig = {
  slug: 'redirects',
  admin: {
    useAsTitle: 'sourcePath',
    defaultColumns: ['sourcePath', 'targetPath', 'updatedAt'],
    description:
      'Automatically maintained when a published post/project slug changes. Owners may also add manual redirects here.',
  },
  access: {
    // Path-mapping metadata only, not user content: safe to read publicly
    // so the app's own redirect-resolution logic (used for anonymous
    // visitors following an old link) can query it without a privileged
    // Local API call.
    read: () => true,
    create: isOwner,
    update: isOwner,
    delete: isOwner,
  },
  fields: [
    {
      name: 'sourcePath',
      type: 'text',
      required: true,
      unique: true,
      index: true,
      validate: validateInternalPath,
      admin: {
        description: 'Old internal path, e.g. /blog/old-slug',
      },
    },
    {
      name: 'targetPath',
      type: 'text',
      required: true,
      validate: validateInternalPath,
      admin: {
        description: 'Current internal path to send visitors to, e.g. /blog/new-slug',
      },
    },
  ],
}
