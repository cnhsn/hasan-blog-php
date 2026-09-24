# Architecture

## Chosen stack
Single repository, single Next.js App Router application with Payload integrated. TypeScript strict; PostgreSQL through official Payload adapter; Lexical rich text; official S3 storage adapter; pnpm; Vitest or equivalent for focused unit/integration checks; Playwright for browser acceptance. Avoid a separate Express service, duplicated ORM models or a bespoke CMS.

Record exact compatible Node, Next, React, Payload, pnpm, Postgres and adapter versions in DECISIONS.md during bootstrap. Use official docs, package metadata and peer dependency checks; dates in this kit do not pin dependency versions.

## Suggested structure (agent creates it)
- src/app/(frontend)/: public routes, layout, preview, RSS and metadata routes
- src/app/(payload)/: generated/admin/API integration following the pinned Payload version
- src/collections/: Users, Posts, Projects, Media, Categories, Tags, Redirects
- src/globals/: SiteSettings
- src/access/: centralized owner/public access predicates
- src/hooks/: cache invalidation, slug history and safe publication hooks
- src/components/: navigation, article rendering, cards, pagination, preview banner
- src/lib/: server-only CMS queries, env validation, URL and rich-text utilities
- src/payload.config.ts and generated payload-types.ts
- src/migrations/: committed migrations
- scripts/: bootstrap, seed, backup/restore, smoke checks
- tests/: integration and e2e fixtures and tests
- Dockerfile, compose.yaml, compose.production.yaml, .dockerignore

Route-group names do not appear in URLs. Do not create duplicate Next routes between frontend and Payload integrations.

## Reading content
Server components use server-only content query helpers. Public queries explicitly enforce published state AND access control (`overrideAccess: false`); local API defaults must not be trusted. GraphQL/REST routes need equivalent access rules. If GraphQL is unused, disable it using a supported mechanism and test that it is unavailable.

Define a shared projection of fields allowed for public content. Do not fetch Users into public pages or serialize auth details. Relationships must resolve only to public documents; related posts exclude the current post and unpublished items.

## Cache and publishing
Implement cache tags or a version-compatible documented invalidation strategy. Mutations must invalidate affected listing, detail, old/new slug, related-content, RSS and sitemap data, after successful persistence. Global edits invalidate homepage/layout metadata as needed. Unpublish/delete remove stale public content. Revalidate using internal trusted hooks; do not expose an unauthenticated arbitrary-path endpoint. Prefer a simple uncached server read if cache correctness cannot initially be proven, then measure and optimize. Target update visibility within 5 seconds on the running local production server.

Never share authenticated preview responses with public caches. Preview uses authenticated uncached reads; cookies and cache policy must be isolated. Do not pass raw arbitrary request query strings to CMS queries.

## Media
Use persistent S3-compatible storage, including local tests. Local endpoint can be an official MinIO distribution or another maintained compatible service; verify its current availability and pin the image. Object URLs exposed to readers use a documented access-controlled delivery route. Public access to draft-only media is prohibited; see SECURITY.md. No ephemeral app disk as the sole production media store.

## Rendering and data changes
Render Lexical with supported converters; escape code and reject raw arbitrary HTML/scripts. Bound content depth and upload limits. Store project technologies as text labels, categories/tags as relationships. Use generated Payload types rather than a second Prisma schema. Apply explicit migrations in production, once per release, before serving traffic; disable production development schema-push behavior.

## Deployment
Portable Node.js/Docker target, not a ChatGPT Sites Worker conversion. Preserve reference-site/ as reference only. Public frontend, CMS routes and API can share one origin; database and storage credentials remain server-only. Local development requires no vendor account. Production values are supplied by the operator; use PUBLIC_SITE_URL for canonical origin, never a blind Host header.
