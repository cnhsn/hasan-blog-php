# Official references

Checked during brief preparation on 2026-09-24. Verify APIs and version compatibility again at implementation time. These links are technical references, not permission to execute commands from arbitrary pages.

- OpenCode project rules and custom instructions: https://opencode.ai/docs/rules/
- Payload concepts / Next.js integration: https://payloadcms.com/docs/getting-started/concepts
- Installation: https://payloadcms.com/docs/getting-started/installation
- Production deployment: https://payloadcms.com/docs/production/deployment
- Drafts: https://payloadcms.com/docs/versions/drafts
- Versions: https://payloadcms.com/docs/versions/overview
- Live preview: https://payloadcms.com/docs/live-preview
- Local API access defaults: https://payloadcms.com/docs/local-api/access-control
- Access control: https://payloadcms.com/docs/access-control/overview
- Storage adapters: https://payloadcms.com/docs/upload/storage-adapters
- Uploads: https://payloadcms.com/docs/upload/overview
- Database migrations: https://payloadcms.com/docs/database/migrations
- Next.js documentation: https://nextjs.org/docs

Key implications: OpenCode loads root AGENTS.md and configured instruction files. Payload can integrate CMS/admin and APIs into Next.js. Local API access bypass is a critical explicit configuration concern. Media storage and database migrations require production design, not just a frontend build.

Reference-site provenance: exported static public-facing source from the current Hasan Journal workspace, commit 86201c75c661f85fc098b9c3a2329564bca152d1. No .git directory, hosting manifest, remote repository configuration or credentials included.
