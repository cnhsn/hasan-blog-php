import type { CollectionConfig } from 'payload'

import { denyCreate, denyDelete, isOwner, isSelf } from '@/access'
import { restrictUserCreation } from '@/hooks/restrictUserCreation'

/**
 * Single-owner auth collection (see docs/SECURITY.md "Auth and initial
 * ownership"). There is no public registration and no in-app "create
 * user" path: `create` is always denied through the API/admin UI. The
 * only way a user document is ever created is `pnpm admin:bootstrap`
 * (scripts/admin-bootstrap.mjs), which uses the Payload Local API with
 * `overrideAccess: true` after confirming zero existing users — a
 * deliberate, documented exception to normal access control, run only
 * from a trusted local/server CLI, never from a request handler.
 */
export const Users: CollectionConfig = {
  slug: 'users',
  admin: {
    useAsTitle: 'displayName',
    hidden: true,
  },
  auth: {
    tokenExpiration: 7200,
    maxLoginAttempts: 5,
    lockTime: 10 * 60 * 1000,
    cookies: {
      sameSite: 'Lax',
      secure: process.env.NODE_ENV === 'production',
    },
  },
  access: {
    create: denyCreate,
    read: isOwner,
    update: isSelf,
    delete: denyDelete,
  },
  fields: [
    {
      name: 'displayName',
      type: 'text',
      required: true,
      maxLength: 80,
      defaultValue: 'Hasan',
    },
    {
      name: 'role',
      type: 'select',
      required: true,
      defaultValue: 'owner',
      options: [{ label: 'Owner', value: 'owner' }],
      access: {
        // Fixed for the lifetime of the document: never editable, even
        // by the owner themself.
        update: () => false,
      },
      admin: {
        readOnly: true,
      },
    },
  ],
  hooks: {
    beforeOperation: [restrictUserCreation],
  },
}
