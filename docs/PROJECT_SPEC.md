# Product specification

## Goal and audience
A fast personal website for Hasan, with a single-screen introduction, public technical writing, project case studies, and a private owner CMS. Preserve the provided design. Content editing must not require source changes or a redeploy for each post.

## V1 scope
| Route | Required behavior |
|---|---|
| / | CMS-managed introduction, nav and interest labels; fit common normal-zoom desktop/portrait mobile screens |
| /blog | Published post list, category filter and pagination, meaningful empty state |
| /blog/[slug] | Published article with title, excerpt, optional cover, date, reading time, rich text, code blocks and related posts |
| /projects | Published project grid/list, technology labels, lifecycle status, pagination |
| /projects/[slug] | Case study, galleries, problem/approach/results, optional validated demo/repository links |
| /admin | Payload private owner UI |
| /preview | Authenticated draft-preview entry, validated internal destination |
| /preview/exit | Leave preview and clear draft state |
| /sitemap.xml | Canonical public pages and published content only |
| /robots.txt | Public crawl policy; exclude admin/preview (not an access-control mechanism) |
| /rss.xml | Latest published blog entries, valid escaped XML |
| /api/health | Minimal health status, no secrets/internal diagnostics |

Unknown slugs return a real 404. Old slugs redirect permanently to the current published slug; draft/removed destinations must not leak. Bad page parameters are normalized or rejected consistently. Category filters remain in URL with pagination; changing category resets page to one. Default page size 9. Do not build search in V1.

## User flows
1. Owner securely bootstraps the only admin, signs in and edits site settings.
2. Owner uploads an image, sets alt text, writes a draft and saves. Draft survives app restart.
3. Owner previews unpublished changes on desktop/mobile with a clear preview banner.
4. Owner publishes. Listing, detail page, related posts, RSS and sitemap update promptly.
5. Owner revises an already published article as a draft. Visitors continue seeing the last published revision until explicitly republished.
6. Owner unpublishes a post. Public detail becomes unavailable and all public discovery surfaces remove it.
7. Owner creates a project with screenshots and optional links, previews and publishes it.
8. Owner reviews/restores an older version as a draft before publishing.
9. Owner resets a forgotten password through configured email; local tests use a mail capture service.

## Content policy
Initial owner identity is Hasan. Use the provided homepage introduction as initial settings. No inferred surname, email, employer, financial information or other private details. Initial public post/project collections are empty. Local demo data must be clearly labeled sample content and disabled in production.

## Quality and boundaries
Readable server-rendered public content, semantic headings, keyboard navigation, responsive images, accessible contrast, reduced-motion support. Homepage no-scroll goal applies at 100% zoom for 1440x900, 1280x720, 390x844 and 375x667; allow vertical scrolling at 200% text/zoom, very short landscape viewports and increased content length. Never clip content or lock scrolling to meet the goal. Article and project detail pages scroll normally.

V1 excludes scheduled publishing, multilingual routing, comments, memberships, public user registration, newsletter sending and custom page builders. Future enhancements must not delay the required release.

## Done
All required acceptance criteria pass against a real database and storage service locally. A deployable Docker artifact and tested runbook exist. Live deployment is a separate status requiring authorized credentials and a target host.
