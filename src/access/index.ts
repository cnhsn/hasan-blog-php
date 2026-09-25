import type { Access, FieldAccess } from 'payload'

/**
 * Hasan Journal is single-owner (see docs/CONTENT_MODEL.md). Any
 * authenticated request user *is* the owner, so "authenticated" and
 * "owner" are the same check in V1. Keep the name `isOwner` rather than
 * `isAuthenticated` so a future multi-editor change starts from an
 * explicit owner check instead of a generic auth check.
 */
export const isOwner: Access = ({ req }) => Boolean(req.user)

export const isOwnerFieldLevel: FieldAccess = ({ req }) => Boolean(req.user)

/**
 * Standard Payload drafts read pattern: the owner (in the admin UI or an
 * authenticated API call) sees every document regardless of status;
 * anyone else is restricted to published documents only. Never bypass
 * this with `overrideAccess: true` on a public-facing query.
 */
export const publishedOrOwner: Access = ({ req }) => {
  if (req.user) return true
  return {
    _status: {
      equals: 'published',
    },
  }
}

/**
 * Media has its own explicit `visibility` field (default private) per
 * docs/CONTENT_MODEL.md/SECURITY.md, independent from Posts/Projects
 * draft/published status. Applies uniformly to JSON metadata reads and
 * to the file-delivery route (Payload checks the same collection
 * access for both), so this is the single guard for "guarded public
 * delivery only after owner marks asset public".
 */
export const publicOrOwnerMedia: Access = ({ req }) => {
  if (req.user) return true
  return {
    visibility: {
      equals: 'public',
    },
  }
}

/** Nobody may create documents through the API/admin UI create form. */
export const denyCreate: Access = () => false

/** Nobody may delete documents through the API/admin UI. */
export const denyDelete: Access = () => false

/** Only the authenticated owner may update their own user document. */
export const isSelf: Access = ({ req }) => {
  if (!req.user) return false
  return {
    id: {
      equals: req.user.id,
    },
  }
}
