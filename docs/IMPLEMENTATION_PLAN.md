# Implementation milestones

Mark each task complete only with evidence in PROGRESS.md. Do not stop after a milestone unless blocked by an actual prerequisite or context limit.

## M0 — Bootstrap
- [ ] Inspect kit and existing files; verify official compatible stable versions.
- [ ] Scaffold Next/Payload in root without overwriting kit; pin versions/lockfile.
- [ ] Create environment validation, .gitignore, containerized local data/media/mail services.
- [ ] Make dev server and private admin login work; safe first-owner CLI.
Gate: clean local services start, admin signs in, no public self-registration.

## M1 — Real CMS
- [ ] Implement all collections/globals, ACL, migrations and generated types.
- [ ] Implement draft/autosave/version behavior, media constraints and safe rendering.
- [ ] Idempotent base seed: settings only, no fabricated public content.
- [ ] Publish one test post through owner UI and retrieve only published revision anonymously.
Gate: content survives restart; anonymous mutation, drafts and versions denied.

## M2 — Frontend
- [ ] Port homepage design, nav/footer and empty states.
- [ ] Build post/project listings and details, category URL filtering and pagination.
- [ ] Wire site settings, responsive media, metadata, RSS, sitemap and 404s.
- [ ] Slug history redirects and publish/unpublish invalidation.
Gate: all routes render real CMS data; no source edit needed to publish.

## M3 — Preview and motion
- [ ] Secure preview + exit + owner banner; uncached draft reads.
- [ ] Port accessible transitions and microinteractions across routes and controls.
- [ ] Verify mobile, zoom, reduced motion, history and keyboard interaction.
Gate: private preview never changes visitor responses; UI has no clipping or broken links.

## M4 — Production packaging
- [ ] Dockerfile and Compose dev/production definitions, readiness checks, non-root runtime.
- [ ] Migration lifecycle, S3 configuration, SMTP, initial-owner and domain instructions.
- [ ] Backup/restore scripts plus local restore drill into separate resources.
- [ ] CI checks on clean checkout with disposable service instances.
Gate: documented production-style container workflow runs locally with persisted data.

## M5 — Verification and handoff
- [ ] Complete ACCEPTANCE.md cases; fix failures and record evidence.
- [ ] Produce actual command README, DECISIONS.md, VERIFICATION.md and final status.
- [ ] Record cloud-only blockers; no false live-deployment claim.
Gate: user can start locally, sign in, upload, write, preview, publish and read without coding.
