# Runtime and deployment contract

These are requirements for the implementation agent. The initial specification kit does not contain executable application commands yet. Create them, test them, then document actual invocations in README.md.

## Developer commands to deliver
| Command | Contract |
|---|---|
| pnpm setup:local | Non-destructive env initialization with random local secrets; preserve existing .env |
| pnpm services:up | Start local Postgres, S3-compatible service, mail capture; wait for readiness |
| pnpm services:down | Stop services without deleting volumes |
| pnpm db:migrate | Apply committed migrations exactly once as supported by Payload |
| pnpm admin:bootstrap | Prompt for owner email/password securely; refuse an existing owner |
| pnpm seed | Idempotent default site settings only |
| pnpm seed:demo | Explicit opt-in local fixtures; refuse production |
| pnpm dev | Next development server |
| pnpm typecheck / lint | Static checks without silently suppressing errors |
| pnpm test / test:e2e | Integration/unit tests and browser tests |
| pnpm build / start | Production build and server |
| pnpm backup | Consistent database dump + media inventory/copy to protected destination |
| pnpm restore:check | Restore a selected backup into isolated resources and verify it |

Use cross-platform Node scripts for orchestration instead of Bash-only env syntax. Document Windows PowerShell and WSL/Linux paths. Docker Desktop/Engine must be running. Never implement `services:down` with volume deletion. Any reset command must be explicit and require confirmation of target.

## Environment
Use supplied .env.example as a contract and reconcile names in code. Validate at startup using a typed schema. Browser receives only deliberately public values. setup:local generates local secrets, bucket and database credentials and .env; never overwrites existing operator configuration. Production rejects localhost site origin and development credentials. SMTP auth may be empty for local mail capture only.

Local app port 3000; PostgreSQL/S3/mail UI ports bind loopback, not all interfaces. Service names and endpoint hostnames differ inside containers versus a host dev process; generate/document both deliberately. Local storage bucket initialization must be idempotent and private. Images/containers use verified pinned versions, not `latest`.

## Production target
Single Docker-capable Linux host with TLS reverse proxy. Next/Payload app container + PostgreSQL (or external managed PostgreSQL); persistent private S3-compatible storage; external SMTP. Do not expose PostgreSQL or admin infrastructure ports to the public internet. `/admin` remains reachable through the application only, protected by auth. Secure the first owner before public traffic.

Deliver a multi-stage Dockerfile using supported Next/Payload output, non-root runtime where practical, production health checks, restart policy and graceful shutdown. Build in CI against isolated build/test services if CMS build requires DB access; never point build at production just to satisfy static collection. Prefer public route runtime rendering when it avoids build-time production data access. Avoid copying .env or credentials into layers. Runtime env/secrets injected by operator.

## Release sequence
1. Run local tests and production-style container smoke checks.
2. Make an off-host encrypted backup and verify restore readiness.
3. Build immutable artifact; apply reviewed database migrations through one controlled process.
4. Start app, check health/auth/public routes, then route traffic.
5. Record release version, migration state and artifact identity.

Avoid multiple replicas racing to apply migrations. For incompatible schema changes, use expand/contract migrations or a maintenance window. Rollback must consider database compatibility; an old container alone is not a safe database rollback.

## Backup and restore
Deliver scripts and a runbook: consistent pg_dump, media object copies/checksums, configuration inventory without plaintext secrets, encrypted off-host storage. Proposed policy: daily backup, 7 daily + 4 weekly copies, monthly restore check; configurable to operator needs. A database-only dump is not a complete backup. Explain consistency during writes and take a brief write pause for the V1 drill if needed. Do not automatically delete live objects or restore into production.

Verify restoring to a new database and bucket can reproduce one published post, one draft and their images. Record checksums/counts. Report actual measured recovery time; no unsupported zero-data-loss guarantee.

## CI and observability
Deliver CI for install --frozen-lockfile, generation/migration checks, typecheck, lint, tests and build on isolated services. Keep migrations/generated types tracked and detect drift. Request logs include IDs/status/duration, exclude bodies/cookies/secrets. Health endpoint exposes minimal status. Log failed media/DB requests and document operator diagnosis. Paid monitoring is not a prerequisite.
