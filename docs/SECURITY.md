# Security requirements

## Auth and initial ownership
Use Payload authentication, secure HTTP-only cookies in production and its supported password hashing/reset lifecycle. Public visitors are anonymous. Protect collection/global mutations and Users reads. Bootstrap the first owner only via an explicit local/server CLI with secret input; fail if an owner already exists. Disable unattended public first-user setup before any internet exposure. Never ship admin/admin or seeded production passwords. Explain the tested bootstrap process.

Use explicit request auth on preview and admin-only application endpoints. CORS/CSRF allow only configured same-origin production/local URLs. Apply login/reset abuse controls through supported Payload configuration and/or a documented reverse proxy rule. Test failed/expired reset tokens; local reset emails go to mail capture, not external recipients.

## Draft isolation
Every public query restricts published state. Payload Local API can bypass ACL by default, so use overrideAccess:false and appropriate user context. Explicitly privileged calls are allowed only inside trusted owner bootstrap or isolated test seed scripts, and must be documented. Test anonymous REST, GraphQL if enabled, relationship population, lists, detail URLs, RSS, sitemap, version endpoints and preview.

Authenticated draft preview must verify an owner session, restrict redirect paths to allowed internal post/project routes, use no-store/private responses and avoid shared cache pollution. A signed preview token alone must not become a public indefinite draft link. Never enable preview from an unsigned `?draft=true`. Clear preview state on exit. Test two independent browser contexts (owner and visitor) at the same time.

## Uploads and media
Private S3 bucket; do not assume CMS access rules protect a public object URL. Default upload visibility private. Owner delivery can use short-lived signed URLs or authenticated proxy. Public delivery must enforce explicit public visibility; publish validation requires public assets. Asset visibility is independent of article status: after unpublishing an article, assets deliberately marked public remain public until owner makes them private. Document this behavior clearly; do not imply automatic revocation. On changing asset visibility purge/disable any media cache; choose no-store delivery in V1 if revocation cannot be guaranteed.

Restrict accepted decoded image types, byte and dimension limits. Do not accept arbitrary external fetch URLs for image import or transformations. Never expose storage secrets or original private signed URLs in public metadata. Referenced asset deletion must not break published content silently.

## Content and routes
Sanitize allowed rich text renderers and link schemes. Escape RSS/XML and code examples. Reject open redirects, malformed slugs and prototype/injection-like query objects. Cap pagination and populated relationship depth. Configure security headers compatible with Payload and preview; test rather than enabling a CSP that breaks the editor. Production debug output must not reveal stack traces, cookies, connection strings or request bodies.

## Operations
.env*, backups, database volumes, screenshots with admin data and logs are ignored. Public build must contain no PAYLOAD_SECRET, database URL or storage credentials. Validate required environment at startup; fail clearly without printing values. Secrets are random and locally generated, never literal examples copied into production. Separate test and production data. Dependency audit findings are triaged and recorded; avoid unreviewed force upgrades.
