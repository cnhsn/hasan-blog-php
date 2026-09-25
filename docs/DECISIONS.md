# Decision log

## Fixed product decisions
- Existing cobalt editorial design and animations retained.
- Next.js + Payload + PostgreSQL, single application/repository.
- S3-compatible media; no cloud vendor required for local work.
- Single owner, no public registration; English public copy, Turkish user handoff.
- No-scroll homepage at ordinary target viewports, natural scrolling for content/accessibility.
- Independent Docker/Node deployment; no ChatGPT Sites manifest carried over.
- Empty production post/project data; opt-in local samples only.

## Agent must fill compatibility matrix
| Component | Exact version | Official compatibility evidence | Date verified |
|---|---|---|---|
| Node.js | v24.19.0 (host) | Satisfies `next@16.3.6` engines `>=20.9.0`, `payload@3.90.2` engines `^18.20.2 \|\| >=20.9.0`, `sharp@0.35.4` engines `>=20.9.0`, `vitest@5.0.1` engines `^22.12.0 \|\| ^24.0.0 \|\| >=26.0.0`. Checked via `npm view <pkg> engines`. | 2026-09-24 |
| pnpm | 9.12.0 | Already installed/verified locally (`pnpm --version`). Deliberately not bumped to latest 12.6.0: pnpm 10+ changed the default handling of dependency lifecycle/build scripts (native postinstalls, e.g. for `sharp`/libvips, are blocked unless explicitly approved). Staying on the already-verified 9.x line avoids an unresearched breaking change; revisit if a future session wants the newer default. | 2026-09-24 |
| Next.js | 16.3.6 | `npm view next@16.3.6 engines/peerDependencies`. | 2026-09-24 |
| React / React DOM | 19.3.0 | Satisfies `next@16.3.6` peer `^19.0.0` and `@payloadcms/richtext-lexical@3.90.2` peer `^19.0.1 \|\| ^19.1.2 \|\| ^19.2.1` (caret range covers 19.3.0). | 2026-09-24 |
| Payload + official adapters | 3.90.2 (payload, @payloadcms/next, @payloadcms/db-postgres, @payloadcms/richtext-lexical, @payloadcms/storage-s3, @payloadcms/email-nodemailer, @payloadcms/ui, @payloadcms/graphql) | All pinned to the identical published version; `@payloadcms/next@3.90.2` peer accepts `next` range `>=16.3.3 <17.0.0` which includes 16.3.6. `@faceless-ui/modal@3.0.0` / `@faceless-ui/scroll-info@2.0.0` peers required by richtext-lexical are already direct dependencies of `@payloadcms/ui@3.90.2`, so pnpm resolves them without manual pins. | 2026-09-24 |
| graphql | 16.14.2 | `payload@3.90.2` peer requires `^16.8.1`; 17.0.2 (npm "latest") is out of range and was rejected. | 2026-09-24 |
| TypeScript | 6.0.3 | npm `latest` dist-tag for `typescript` is 7.0.2 (the Go-ported native compiler), but `@typescript-eslint/parser@8.70.1` and `@typescript-eslint/eslint-plugin@8.70.1` (current stable, matching `eslint@10.11.0` and `eslint-config-next@16.3.6`) both declare peer `typescript: ">=4.8.4 <6.1.0"`. TS 7.0.2 is therefore not mutually compatible with the current lint toolchain; 6.0.3 is the newest release inside the supported range. | 2026-09-24 |
| ESLint toolchain | eslint 9.39.5, eslint-config-next 16.3.6, @typescript-eslint/parser+eslint-plugin 8.70.1, esbuild 0.28.2 (explicit, satisfies vite's optional peer) | See deviation note below re: eslint 9 vs 10. | 2026-09-24 |
| sharp | 0.35.4 | Payload does not bundle an image-processing library; official upload docs require installing `sharp` separately for resizing/thumbnails. Engines `>=20.9.0` satisfied by Node 24.19.0. | 2026-09-24 |
| vitest / vite | vitest 5.0.1, vite 8.3.1 | `vitest@5.0.1` peer `vite: ^6.4.0 \|\| ^7.0.0 \|\| ^8.0.0` — 8.3.1 satisfies it. | 2026-09-24 |
| @playwright/test | 1.63.0 | `next@16.3.6` optional peer `@playwright/test: ^1.51.1` — 1.63.0 satisfies (same major). | 2026-09-24 |
| @types/node | 24.13.6 | Matches installed Node 24 major; satisfies vitest peer `@types/node: ^22.0.0 \|\| >=24.0.0`. | 2026-09-24 |
| PostgreSQL (Docker) | `postgres:17-alpine`, server reports `PostgreSQL 17.11` | Pulled and ran locally; `docker run --rm postgres:17-alpine postgres --version`. Official Postgres image, no auth required. | 2026-09-24 |
| S3-compatible storage (Docker) | `dxflrs/garage:v2.4.1` (Garage, Deuxfleurs) | **Deviation from MinIO**: `minio/minio` is no longer pullable from Docker Hub or Quay without auth (`pull access denied` on `latest` and on an older pinned `RELEASE.*` tag alike — verified 2026-09-24), so it cannot serve as the documented "official MinIO distribution". Garage is ARCHITECTURE.md's explicit fallback ("or another maintained compatible service"). Verified end-to-end: booted single-node via `server --single-node --default-access-key --default-bucket` flags (self-provisions a default access key + private bucket, no manual cluster/bucket bootstrap step needed), then ran a real `boto3` S3 client roundtrip (list/put/head/get/delete) against `http://127.0.0.1:3900` — all succeeded. | 2026-09-24 |
| Mail capture (Docker) | `axllent/mailpit:v1.31.2` | Pulled and ran; binary reports `mailpit v1.31.2`; exact tag confirmed present on Docker Hub (not floating `latest`). | 2026-09-24 |

Record material deviations with reason, alternatives considered and acceptance impact. Do not silently change required stack or drop scope because a test is difficult.

### Deviation: MinIO → Garage for local/dev S3-compatible storage
- **Reason**: MinIO's container images are no longer publicly pullable (verified denied on Docker Hub `minio/minio:latest`, Quay `minio/minio:latest`, and an older pinned MinIO `RELEASE.*` tag — all return access-denied without a Docker Hub login as of 2026-09-24).
- **Alternatives considered**: SeaweedFS (pulls fine, but needs master+volume+filer topology even for a single node — more moving parts for local dev than justified here); LocalStack (broader AWS emulation than needed, heavier).
- **Choice**: Garage (`dxflrs/garage`), a maintained open-source S3-compatible object store built for exactly this self-hosted single/small-node use case. Explicitly allowed by ARCHITECTURE.md ("Local endpoint can be an official MinIO distribution or another maintained compatible service; verify its current availability and pin the image").
- **Acceptance impact**: None expected — Payload's `@payloadcms/storage-s3` talks to any S3-compatible endpoint via the AWS SDK v3; the verified roundtrip (put/get/head/delete against a private bucket with path-style addressing, `signatureVersion: v4`) covers the operations Payload uses for uploads. Production deployments remain free to point at MinIO, AWS S3, or any other S3-compatible provider the operator controls — this deviation only affects the local/dev/test compose stack pinned in this repo.

### Deviation: ESLint pinned to 9.39.5, not the npm-`latest` 10.11.0
- **Reason**: `eslint@10.11.0` is npm's `latest` dist-tag, and `eslint-config-next@16.3.6`'s own top-level peerDependencies claims `eslint: ">=9.0.0"` (which reads as satisfied). But a real `pnpm install` surfaced that `eslint-config-next`'s *bundled* dependencies — `eslint-plugin-import@2.32.0`, `eslint-plugin-react@7.37.5`, `eslint-plugin-jsx-a11y@6.10.2` — each still declare `peerDependencies.eslint` capped at `^9` (none accept `^10`). Installing eslint 10 produced three unmet-peer errors; the loosely-declared top-level range on `eslint-config-next` does not reflect its own sub-plugins' real constraints.
- **Choice**: eslint 9.39.5 (latest 9.x). This satisfies `@typescript-eslint/{parser,eslint-plugin}@8.70.1`'s peer (`^8.57.0 || ^9.0.0 || ^10.0.0`) and all three bundled Next lint plugins. `pnpm install` afterwards resolved with zero unmet-peer warnings.
- **Known cost**: npm marks eslint 9.x as a deprecated/unsupported major line (end-of-support notice on install). This is accepted as the only currently mutually-compatible choice; revisit and re-run this check once `eslint-config-next` ships a release with eslint-10-compatible plugin peers.
- **Also fixed in the same pass**: `vite@8.3.1` declares `esbuild` as an *optional* peer (`^0.27.0 || ^0.28.0`) for its legacy transform path; a transitively-resolved `esbuild@0.25.12` (from an unrelated subdependency) triggered an unmet-optional-peer warning. Added `esbuild@0.28.2` as an explicit devDependency to resolve it cleanly, even though it is optional and not strictly required for vitest's Node-environment tests.

### Security finding: Payload's built-in "create first user" bypasses `access.create`
Verified in `node_modules/payload/dist/auth/operations/registerFirstUser.js`: when the Users collection is empty, Payload's admin panel serves an **unauthenticated** create-first-user form, and the operation behind it calls `payload.create(..., { overrideAccess: true })` — it does not consult the collection's `access.create` at all, by design (it has to solve the bootstrap chicken-and-egg problem somehow). Setting `access.create: () => false` alone (as docs/SECURITY.md's single-owner model otherwise calls for) does **not** close this: an attacker reaching `/admin` before the operator runs the bootstrap script could still self-register as the owner.
- **Fix**: `src/hooks/restrictUserCreation.ts`, a `beforeOperation` hook on Users, rejects every `create` unless `context.isBootstrapCli` was explicitly set — and the only code that sets it is `scripts/admin-bootstrap.mjs`. Hooks run for every caller of `payload.create()` regardless of which HTTP route or Local API caller invoked it, so this also covers `registerFirstUserOperation`. This turns "never expose the app before bootstrapping" from a purely operational rule into a real, testable technical control (verifiable by hitting `/admin` with zero users and confirming the create-first-user submission is rejected).
- Documented here because it is exactly the kind of Payload default that SECURITY.md calls out ("Disable unattended public first-user setup before any internet exposure") and is easy to miss if you only set `access.create`.

### Caching strategy: start uncached, add cache tags only if measurement shows a need
ARCHITECTURE.md explicitly allows: "Prefer a simple uncached server read if cache correctness cannot initially be proven, then measure and optimize." Public pages read through Payload's Local API directly (not Next's `fetch()`), so Next's `fetch`-based data cache does not apply to them automatically, and Next.js 16 ships an explicit-opt-in Cache Components model (`"use cache"` + `cacheTag`/`cacheLife`) that is easy to misapply incorrectly under time pressure. Decision: every data-driven route (`/`, `/blog`, `/blog/[slug]`, `/projects`, `/projects/[slug]`, `/sitemap.xml`, `/rss.xml`) sets `export const dynamic = 'force-dynamic'` and reads Payload fresh on every request — no cache layer, no invalidation hooks needed. This trivially satisfies the "<5s update visibility" requirement (it is immediate) at the cost of an extra DB round-trip per request, which is acceptable for a personal-blog traffic profile. Revisit with `cacheTag`/`cacheLife` only if real measurement shows it is needed.

### Media rendering: Payload size variants, not next/image
Payload builds media URLs from `serverURL`, so they are absolute
(`http://localhost:3000/api/media/file/x.png`). `next/image` rejects absolute
URLs whose hostname is not in `images.remotePatterns`, which made every
listing containing a post with a cover render as an error boundary — found
only after seeding demo content. Whitelisting hostnames would mean
per-environment image config. Instead `src/components/MediaImage.tsx`
normalises the URL to a same-origin path and builds `srcSet` from the
variants Payload already generated (`Media.upload.imageSizes`). This also
stops the Next optimizer from re-encoding already-resized images, and avoids
its server-side fetch running without the viewer's cookies — which would
break owner previews of assets that are not public yet.

### Payload's Local API drops the `draft` flag on `restoreVersion`
`payload.restoreVersion({ id, draft: true })` silently republishes: the local
wrapper (`collections/operations/local/restoreVersion.js`) destructures a
fixed option set and never forwards `draft` to the operation, so the
operation runs with its default `draft: false` and overwrites the live
published row. Verified empirically — the live row changed and stayed
`published`.

The REST endpoint *does* forward `?draft=true`, so the admin UI's
"restore as draft" behaves correctly, which is the path the owner actually
uses. Consequences for this codebase:
- The A28 acceptance test lives in `tests/e2e/versions.spec.ts` and drives the
  REST endpoint, not the Local API.
- Do not call `payload.restoreVersion({ draft: true })` in application code
  expecting it to leave the published revision alone. Read the version and
  `payload.update({ ..., draft: true })` instead.

### Build must not need secrets or a database
`next build` forces `NODE_ENV=production`, which would otherwise trip the
production guards in `src/lib/env.ts` (https-only, no localhost) during an
ordinary local build. Those guards, and the "variable is required" errors,
are now skipped when `process.env.NEXT_PHASE === 'phase-production-build'`,
falling back to obviously-fake placeholders. Runtime validation is unchanged
and still strict. This is what lets the Docker image build with no
environment and no reachable database at all.

Relatedly, the public route group is `force-dynamic`. Without it Next
prerendered `/` and `/sitemap.xml`, which baked CMS content into the build
output and made the build require a live database — both explicitly ruled out
by docs/OPERATIONS.md.

### Container image ships a full dependency tree (~1.4 GB)
Next's `standalone` output was tried and removed. The same image has to run
the controlled migration step, which loads the TypeScript Payload config
through the Payload CLI, so a trimmed server bundle would need a second image
and a second dependency tree to reason about. One image with production
dependencies keeps `pnpm db:migrate` identical in development and production.
The size is acceptable for the documented target (a single Docker host); if it
ever matters, the fix is a separate slim runtime image plus a dedicated
migration image, not partial standalone output.

