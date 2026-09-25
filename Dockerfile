# syntax=docker/dockerfile:1

# Production image for the Next.js + Payload app.
#
# The image deliberately ships a full production dependency tree rather
# than Next's `standalone` output: the same image has to run the
# controlled migration step (`payload migrate`, which loads the
# TypeScript config through the Payload CLI), so a trimmed server bundle
# would need a second image and a second dependency tree to reason
# about. See docs/DECISIONS.md.
#
# No build arguments carry secrets: `next build` runs with no
# environment configured at all and relies on the build-phase
# placeholders in src/lib/env.ts. Runtime configuration is injected by
# the operator (see compose.production.yaml).

ARG NODE_IMAGE=node:24.19.0-bookworm-slim

FROM ${NODE_IMAGE} AS base
ENV PNPM_HOME=/pnpm
ENV PATH=$PNPM_HOME:$PATH
RUN corepack enable && corepack prepare pnpm@9.12.0 --activate
WORKDIR /app

FROM base AS deps
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

FROM base AS prod-deps
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile --prod

FROM base AS builder
COPY --from=deps /app/node_modules ./node_modules
COPY . .
RUN pnpm build

FROM ${NODE_IMAGE} AS runner
ENV NODE_ENV=production
ENV PORT=3000
ENV NEXT_TELEMETRY_DISABLED=1
WORKDIR /app

COPY --from=prod-deps --chown=node:node /app/node_modules ./node_modules
COPY --from=builder --chown=node:node /app/.next ./.next
COPY --chown=node:node package.json tsconfig.json next.config.ts ./
COPY --chown=node:node src ./src

USER node
EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=5s --start-period=45s --retries=3 \
  CMD node -e "fetch('http://127.0.0.1:'+(process.env.PORT||3000)+'/api/health').then(r=>{process.exit(r.ok?0:1)}).catch(()=>process.exit(1))"

# Exec form so the Node process is PID 1 and receives SIGTERM directly,
# which Next handles as a graceful shutdown.
CMD ["node", "node_modules/next/dist/bin/next", "start"]
