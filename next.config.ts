import { withPayload } from '@payloadcms/next/withPayload'
import type { NextConfig } from 'next'

const nextConfig: NextConfig = {
  // Next.js 16 otherwise auto-appends an "agent rules" block to AGENTS.md
  // on every `next dev`. AGENTS.md is a preserved project instruction
  // file (see its own "Preserve this kit" rule) and must not be mutated
  // by tooling, so this is turned off.
  agentRules: false,
  async headers() {
    return [
      {
        source: '/:path*',
        headers: [
          { key: 'X-Content-Type-Options', value: 'nosniff' },
          { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
          { key: 'X-Frame-Options', value: 'SAMEORIGIN' },
          { key: 'Permissions-Policy', value: 'camera=(), microphone=(), geolocation=()' },
        ],
      },
    ]
  },
}

// A strict Content-Security-Policy is deliberately not set yet — Payload's
// admin panel relies on inline styles/scripts, and docs/SECURITY.md
// explicitly warns against enabling a CSP that hasn't been tested against
// it. Revisit with a CSP scoped to /admin vs the public routes.
export default withPayload(nextConfig, { devBundleServerPackages: false })
