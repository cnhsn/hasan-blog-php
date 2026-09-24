# Acceptance tests

Tests must execute against real PostgreSQL and a real local S3-compatible service. Mocked success responses do not satisfy persistence/security cases. Use a dedicated disposable test database/bucket. Restore drill uses separate resources; never wipe a user database.

| ID | Scenario | Expected evidence |
|---|---|---|
| A01 | Fresh checkout setup | Documented install/services/migrate/bootstrap/dev sequence works with no cloud keys |
| A02 | Restart persistence | Saved post and uploaded image remain after app and service restart |
| A03 | Owner bootstrap | First owner succeeds, second bootstrap refused; anonymous account creation denied |
| A04 | Anonymous authorization | REST mutations, Users and versions reads denied; GraphQL same or disabled |
| A05 | Draft isolation | Draft not visible in list/detail/relationships/RSS/sitemap/API; guessed URL returns no content |
| A06 | Owner editing | Create/edit post in UI; autosave recovers edits; validation errors readable |
| A07 | Published revision | Editing published post as draft preserves visitor's previous revision |
| A08 | Preview isolation | Owner sees draft; concurrent anonymous context does not; exit clears preview |
| A09 | Publish/unpublish | Discovery surfaces update within 5 seconds; unpublished detail no longer visible |
| A10 | Projects | Create project, upload gallery, add labels and links, publish; lifecycle status separate |
| A11 | Media privacy | Anonymous private-media URL denied; explicit public image loads; visibility reversal enforced |
| A12 | Upload validation | Over-limit, fake extension, unsupported type and excessive dimensions rejected |
| A13 | Asset integrity | In-use deletion blocked or references explicitly removed without broken published media |
| A14 | Slug conflicts | Duplicate reserved/bad slugs rejected with actionable errors |
| A15 | Slug history | Published rename yields single permanent redirect; no loops/external targets; unpublished target hidden |
| A16 | Filters/pagination | URL state/back-forward accurate; last page/empty category/invalid page handled |
| A17 | Content rendering | Rich text, code, Unicode, images and malicious link/HTML fixtures render safely |
| A18 | SEO | Correct canonical and metadata on each route; draft/private URLs absent; valid RSS/XML |
| A19 | Empty content | Useful public empty states, no mock articles or dead CTAs |
| A20 | Responsive UI | Target viewports fit homepage; detail pages scroll; 200% zoom remains accessible |
| A21 | Motion | Reduced motion honored; keyboard/new-tab/modifier/back-forward behavior preserved |
| A22 | Password reset | Local mail capture receives reset; expiry/reuse validated; production SMTP config documented |
| A23 | Build | Typecheck/lint/tests and production build pass; build does not require real production secrets |
| A24 | Container | Production-style local container becomes ready; DB/storage restart recovery documented |
| A25 | Backup recovery | Restore database and media to isolated targets; verify post and image integrity |
| A26 | Secrets | No credentials in Git-tracked files or public browser bundles; sanitized logs |
| A27 | CMS ownership | Change homepage text/SEO via admin and verify frontend without source modification |
| A28 | Version restore | Restore a prior revision into draft; public version unchanged until publish |

Browser suite: Chromium desktop + mobile viewport; cover WebKit/Firefox for critical navigation if available, report unavailable engines. Keyboard and reduced-motion tests required. Automated accessibility checks on public pages plus manual focus inspection. Do not promise an arbitrary Lighthouse score; report measured results and conditions if collected.

VERIFICATION.md entries must include case ID, date, command or manual steps, pass/fail/blocked, concise actual result, artifact path where available. Distinguish implementation from executed verification. One failed required security case blocks release readiness.
