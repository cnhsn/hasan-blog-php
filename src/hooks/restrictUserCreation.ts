import type { CollectionBeforeOperationHook } from 'payload'
import { Forbidden } from 'payload'

/**
 * Payload's admin panel has a built-in "create first user" view that
 * runs unauthenticated whenever the Users collection is empty
 * (`registerFirstUserOperation`, called with `overrideAccess: true` —
 * it does not consult `access.create`). docs/SECURITY.md requires
 * disabling unattended public first-user setup entirely and bootstrap
 * only through `pnpm admin:bootstrap` (scripts/admin-bootstrap.mjs).
 *
 * That script is the only caller that passes
 * `context: { isBootstrapCli: true }` into `payload.create()`, so this
 * hook rejects every other create — including the built-in
 * unauthenticated first-user flow — even when the collection is empty.
 */
export const restrictUserCreation: CollectionBeforeOperationHook<'users'> = ({
  context,
  operation,
  req,
}) => {
  if (operation === 'create' && !context?.isBootstrapCli) {
    throw new Forbidden(req.t)
  }
}
