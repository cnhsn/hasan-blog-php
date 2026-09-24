# CMS content model

All models have timestamps and generated types. Validate fields server-side, not only in the editor. Text defaults below are initial data, not fixed runtime content.

## Users (auth collection)
Owner-only system. Fields: email (auth unique), password (Payload-managed), displayName, role restricted to owner. No anonymous creation, listing, role edits or public registration. Creation/reset through controlled owner bootstrap or owner administration. Do not implement multi-editor RBAC in V1. No email or user IDs in public author data; posts use public authorName string default Hasan.

## Posts
| Field | Contract |
|---|---|
| title | Required plain text, 1–160 characters |
| slug | Required unique lowercase ASCII kebab slug, 1–120; generate then editable; validate reserved names |
| excerpt | Required text, max 320 |
| content | Required Lexical document; paragraphs, h2/h3, lists, quotes, links, images, fenced code with language |
| cover | Optional relation to Media |
| authorName | Public display string, default Hasan |
| categories / tags | Many relations; tags max 12 |
| publishedAt | Set at initial actual publication, preserve on edits; timezone UTC storage |
| featured | Boolean default false |
| seo | Optional title <=70, description <=170, shareImage relation |
| _status | Payload draft/published, not a custom duplicate state |

Enable version history, drafts and autosave with a bounded version retention policy (e.g. latest 50 per document, using supported API). Reading time derived from plain text at 200 words/minute, minimum 1; do not count JSON keys/markup. Future dates do not act as a scheduler; scheduled publishing is out of scope.

## Projects
Title, unique slug and shortDescription with the same limits as posts. Required rich-text caseStudy with optional structured problem/approach/results sections; optional cover, gallery (max 12 ordered media relations with captions), technologies (max 12 text labels), repositoryURL and liveURL (https/http only), featured flag, startDate and optional endDate. End date cannot precede start date. Lifecycle status: idea / in-progress / completed / archived. Lifecycle status is independent of CMS draft/published status. Drafts, autosave and versions as for Posts. SEO fields as for Posts. Empty optional links render no button.

## Media
File, required meaningful alt text for informational images, optional caption, detected MIME, original size and generated dimensions/variants. Support JPEG, PNG, WebP and AVIF only for V1; max 10 MiB/file; validate dimensions and cap decoded pixel count (e.g. 40 MP). Reject SVG/HTML/executables and unsupported polyglots based on decoding, not extension alone. Track access policy public/private explicitly. Default private. Use guarded public delivery only after owner marks asset public; publication validation requires all referenced media to be public, with clear admin error. Private assets remain owner-only even at known URLs. Deletion of an in-use asset must be blocked with readable references or require unlinking first.

## Categories / Tags
Name and unique slug. Owner CRUD. Public taxonomy reads may return only safe name/slug metadata; counts, where shown, count only published entries. Deleting a taxonomy reference should clear it safely, never cascade-delete posts.

## Redirects
Owner-controlled plus automatic slug-history hook: internal sourcePath unique, targetPath validated internal path, fixed permanent status. Reserve admin/api/preview paths. Prevent loops and chains; flatten to final destination. Only redirect to currently published public documents. Unpublish/delete must deactivate affected redirects. Never permit javascript:, external targets or protocol-relative URLs.

## SiteSettings (global)
Site title, short description, author display name, hero greeting, intro heading/body, interests (max 5), copyright name, social links (label + validated https URL), default SEO title/description. Navigation has Home/Blog/Projects labels and fixed internal destinations. Homepage text limits keep the intended composition reasonable; overflow must remain accessible. Seed from reference-site/index.html; no invented social links.

## Publication rules
Require valid slug, nonempty content and valid referenced assets before publish. Draft may be incomplete but explicit preview must handle missing optional data. Validate title/slug uniqueness transactionally via database constraints. Handle duplicate slug with a useful editor error. Restoring history creates an editable draft; it must not silently replace the public revision.
