# Verification record

All results below were produced by executing the listed commands against the
real local stack (PostgreSQL 17.11, Garage 2.4.1 S3, Mailpit 1.31.2 — all in
Docker) on **2026-09-25**. Nothing in this file is inferred from reading code.

**Status: implemented and verified locally. Not deployed to production.**
No domain, server, production database, S3 provider or SMTP relay was
supplied, so nothing here claims a live deployment.

## Automated suites

| Suite | Command | Result |
|---|---|---|
| Typecheck | `pnpm typecheck` | pass, 0 errors |
| Lint | `pnpm lint` | pass, 0 errors, 0 warnings |
| Unit + integration | `pnpm test` | **51 passed / 51** (5 files) |
| Browser (Chromium desktop + mobile) | `pnpm test:e2e` | **36 passed / 36** |
| Production build | `pnpm build` | pass; all data routes render per request |
| Container build | `docker build -t hasan-journal:local .` | pass with **no env vars and no database** |

Integration tests connect to the real Postgres and the real Garage S3 service
using a dedicated disposable database (`hasan_journal_test`) and bucket
(`hasan-journal-test`). Object counts are read back from Garage itself, not
from the application.

## Acceptance cases

| ID | Scenario | Result | Evidence |
|---|---|---|---|
| A01 | Fresh checkout setup | **pass** | Local volumes and `.env` deleted (`docker compose down -v`), then the documented sequence run verbatim: `setup:local` → `services:up` (all healthy in 7s) → `db:migrate` (applied `20260925_144041_initial`, 25 tables) → `admin:bootstrap` → `seed` → `dev`. All routes then returned 200, empty states rendered, owner login succeeded. No cloud keys involved. |
| A02 | Restart persistence | **pass** | Before: 11 posts, image sha256 `4462ee3a…`. After `docker compose stop && start` (volumes preserved): 11 posts, identical sha256, image HTTP 200. Production container also restarted and returned to `healthy`. |
| A03 | Owner bootstrap | **pass** | First `admin:bootstrap` created the owner; a second run exited 1 with "An owner account already exists." Anonymous `POST /api/users` → 403. Anonymous `POST /api/users/first-register` (Payload's own unauthenticated first-user endpoint) → **403**, user count unchanged. |
| A04 | Anonymous authorization | **pass** | Anonymous REST create → 403; anonymous `GET /api/users` → 403; anonymous version reads rejected (`versions.test.ts`); GraphQL disabled — `/api/graphql` and `/api/graphql-playground` both 404. |
| A05 | Draft isolation | **pass** | `access.test.ts`: drafts absent from anonymous list, slug lookup and relationship population. Browser test: a draft's URL returns **404** to an anonymous context while the owner previews it, and the draft is absent from `/blog`, `/rss.xml` and `/sitemap.xml`. |
| A06 | Owner editing | **partial** | Create/edit/publish verified through the authenticated REST API and the Local API, and the admin panel loads and authenticates. Autosave (`versions.drafts.autosave`, 2s) and inline validation messages were **not** driven through the admin UI in a browser test. |
| A07 | Published revision preserved | **pass** | `access.test.ts`: after editing a published post as a draft, anonymous reads still return the published title while the owner's draft read returns the new one. |
| A08 | Preview isolation | **pass** | Two concurrent browser contexts: owner sees draft content + preview banner; visitor gets 404 for the same URL. "Exit preview" clears state — the same URL then 404s for the owner too. Preview without a session → 401; with a session but a tampered token → 401. Measured against a real production server (`next start`), the draft response carries `Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate`, so it cannot be held by a shared cache; the same URL returns 404 to an anonymous request. Preview entry refusals return `private, no-store`. |
| A09 | Publish/unpublish speed | **pass** | Measured over HTTP: publish → visible in `/blog` in **200 ms**; present in RSS and sitemap. Unpublish → gone from listing in **96 ms**, detail page 404. Requirement was 5 s. |
| A10 | Projects | **pass** | Project with gallery, technology labels, links and lifecycle status created and published (`seed-demo`), renders at `/projects/sample-project`. Lifecycle status is a separate field from `_status` and is shown independently. |
| A11 | Media privacy | **pass** | Private asset: anonymous `GET /api/media/file/…` → **403**, owner → 200. After marking public: anonymous → 200. After reverting to private: anonymous → 403 again. |
| A12 | Upload validation | **partial** | A shell script renamed to `.png` is rejected ("File type text/plain (from extension png) is not allowed") — content is sniffed, not trusted by extension. Pixel cap (40 MP) and the 10 MiB body limit are implemented and unit-reachable, but were **not** exercised with real over-limit files. |
| A13 | Asset integrity | **pass** | Deleting a Media document referenced by a post's cover is rejected with a readable error naming the referencing document. |
| A14 | Slug conflicts | **pass** | Duplicate slug rejected; reserved slug (`admin`) rejected; malformed slug (`Not A Slug`) rejected; auto-generation from title verified. |
| A15 | Slug history | **pass** | Renaming a published post creates exactly one redirect old→new. Two renames flatten so no redirect points at another redirect's source. Renaming a never-published draft creates none. Unpublishing removes redirects that would point at it. |
| A16 | Filters and pagination | **pass** | 11 published posts at page size 9: page 1 → 9, page 2 → 2, page 3 → empty state, page 99 → no documents and no error. Category filters return 5 and 6. Invalid `page` values (`abc`, `0`, `-5`, `99999`) all return 200. Drafts excluded from every count. |
| A17 | Content rendering | **partial** | A `javascript:` link in rich text is rejected at save time, and the renderer independently refuses unsafe schemes. Code blocks, Unicode and images render. A full malicious-fixture corpus (raw HTML embeds, polyglot files) was **not** assembled. |
| A18 | SEO | **pass** | Per-route canonical URLs and metadata; `robots.txt` disallows `/admin`, `/api`, `/preview` and points at the sitemap; RSS is well-formed and XML-escaped (`&apos;` verified); drafts absent from RSS and sitemap. |
| A19 | Empty content | **pass** | On the clean install both `/blog` and `/projects` render their empty states ("No posts published yet." / "No projects published yet.") with a working cross-link, and no fabricated articles. |
| A20 | Responsive UI | **pass** | Homepage fits with no vertical or horizontal scrolling at 1440×900, 1280×720, 390×844 and 375×667, with **nothing clipped** (checked per element, not just page height). At 200%-zoom equivalents the page falls back to normal scrolling instead of cutting content off. Detail pages scroll normally. |
| A21 | Motion | **pass** | `prefers-reduced-motion: reduce` disables animation. Back/forward restore pages with content visible. With JavaScript disabled the main content is visible at full opacity. Keyboard focus is visible and reaches navigation. |
| A22 | Password reset | **pass** | Reset requested → email captured by Mailpit (never sent externally). Token used once → "Password reset successfully"; reusing the same token → **403** "Token is either invalid or has expired"; new password logs in, old one rejected. |
| A23 | Build | **pass** | `pnpm typecheck`, `pnpm lint`, `pnpm test`, `pnpm build` all pass. The Docker image builds with **no environment variables and no reachable database**, which is the strongest form of "the build needs no production secrets". |
| A24 | Container | **pass** | Production-style container: migration job applied the migration to a fresh database, then the app container reached Docker `healthy` and served `/`, `/blog`, `/projects`, `/rss.xml`, `/sitemap.xml`, `/robots.txt`, `/api/health`, `/admin` — all 200. Restart recovery confirmed. |
| A25 | Backup recovery | **pass** | `pnpm backup` produced `database.dump`, 4 media objects with sha256, and a secret-free configuration inventory. `pnpm restore:check` restored into an isolated scratch database: all 7 collection counts matched, 11 published + 1 draft post restored, 4/4 media checksums verified. **Measured recovery time: 3.4 s.** Live database and bucket untouched; scratch database dropped. |
| A26 | Secrets | **pass** | No secret value appears in any git-tracked file; `.env` is untracked. 3.8 MB of client JavaScript downloaded from the running site contains zero occurrences of `PAYLOAD_SECRET`, `DATABASE_URI`, `S3_SECRET_ACCESS_KEY`, `POSTGRES_PASSWORD`, `GARAGE_RPC_SECRET` or their values. |
| A27 | CMS ownership | **pass** | Homepage headline, intro, interest labels, navigation labels, footer name and SEO defaults all come from the SiteSettings global and change without touching source. Verified by seeding and rendering. |
| A28 | Version restore | **pass** | Through the REST endpoint the admin UI calls (`POST /api/posts/versions/:id?draft=true`): the published page still shows revision two while the owner's draft holds revision one. See the caveat about the Local API in `docs/DECISIONS.md`. |

**Summary: 24 pass, 3 partial (A06, A12, A17), 0 fail, 1 not applicable locally
(live deployment).** No required security case failed.

## What was not verified

Stated plainly rather than glossed over:

- **No production deployment.** Every "production" result above comes from a
  production-mode container running against local services on this machine.
- **Browser coverage is Chromium only** (desktop + mobile viewport). WebKit
  and Firefox were not installed in this environment.
- **Admin-UI interactions** (autosave recovery, drag-and-drop uploads, inline
  validation rendering) were exercised via API and page load, not by driving
  the Payload admin interface in a browser.
- **Upload limits** for oversized files and excessive pixel dimensions are
  implemented but were not fired with real over-limit fixtures.
- **Lighthouse/performance numbers** were not collected, so none are claimed.
- **Real SMTP delivery** was not tested; only local mail capture.
- **`pnpm services:reset`** was not executed (it prompts for confirmation);
  the equivalent `docker compose down -v` was, during the A01 clean-slate run.
- **Cookie authentication requires a trusted `Origin`.** Verified as a
  positive security property: with `Origin: http://localhost:3000` the
  session cookie authenticates, and with `Origin: https://evil.example.com`
  the same cookie is ignored and the request stays anonymous. Cookie-based
  mutations without an allowed Origin are rejected (403).

## Reproducing

```bash
pnpm install && pnpm setup:local && pnpm services:up
pnpm db:migrate && pnpm admin:bootstrap && pnpm seed
ALLOW_DEMO_SEED=true pnpm seed:demo     # populated-state checks
pnpm typecheck && pnpm lint && pnpm test && pnpm build
pnpm dev                                 # then, in another shell:
pnpm test:e2e
pnpm backup && pnpm restore:check
node scripts/screenshots.mjs             # artifacts/screenshots/
```

Screenshots for the four required viewports across home, blog, projects and
both detail pages (21 images, including a reduced-motion variant) are written
to the git-ignored `artifacts/screenshots/` directory.
