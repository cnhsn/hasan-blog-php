/**
 * Typed, validated environment access. Import `env` instead of reading
 * `process.env` directly anywhere in server-only code. Never log the
 * values of secrets — error messages below name the variable, not its
 * contents.
 */

/**
 * `next build` forces NODE_ENV=production and sets NEXT_PHASE, but the
 * build must not require real production secrets or a reachable
 * database (docs/OPERATIONS.md A23). During the build phase we fall
 * back to obviously-fake placeholders and skip the production
 * strictness checks; everything is enforced normally at runtime.
 */
function isBuildPhase(): boolean {
  return process.env.NEXT_PHASE === 'phase-production-build'
}

function required(name: string, buildPlaceholder = 'build-phase-placeholder'): string {
  const value = process.env[name]
  if (!value || value.trim() === '') {
    if (isBuildPhase()) return buildPlaceholder
    throw new Error(`Missing required environment variable: ${name}`)
  }
  return value
}

function optional(name: string, fallback: string): string {
  const value = process.env[name]
  return value && value.trim() !== '' ? value : fallback
}

function optionalBool(name: string, fallback: boolean): boolean {
  const value = process.env[name]
  if (value === undefined || value.trim() === '') return fallback
  return value.trim().toLowerCase() === 'true'
}

function loadEnv() {
  const nodeEnv = optional('NODE_ENV', 'development') as 'development' | 'production' | 'test'
  const isProduction = nodeEnv === 'production'

  const publicSiteUrl = required('PUBLIC_SITE_URL', 'https://build-phase-placeholder.invalid')
  const payloadSecret = required('PAYLOAD_SECRET')
  const databaseUri = required(
    'DATABASE_URI',
    'postgres://build:build@build-phase-placeholder.invalid:5432/build',
  )

  if (isProduction && !isBuildPhase()) {
    let parsed: URL
    try {
      parsed = new URL(publicSiteUrl)
    } catch {
      throw new Error('PUBLIC_SITE_URL must be a valid absolute URL in production')
    }
    if (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1') {
      throw new Error('PUBLIC_SITE_URL must not be localhost in production')
    }
    if (parsed.protocol !== 'https:') {
      throw new Error('PUBLIC_SITE_URL must use https in production')
    }
    if (payloadSecret.length < 32) {
      throw new Error('PAYLOAD_SECRET must be at least 32 characters in production')
    }
    if (databaseUri.includes('changeme')) {
      throw new Error('DATABASE_URI still contains the placeholder development password')
    }
  }

  return {
    nodeEnv,
    isProduction,
    publicSiteUrl,
    port: Number.parseInt(optional('PORT', '3000'), 10),
    payloadSecret,
    databaseUri,
    s3: {
      endpoint: required('S3_ENDPOINT'),
      region: optional('S3_REGION', 'us-east-1'),
      bucket: required('S3_BUCKET'),
      accessKeyId: required('S3_ACCESS_KEY_ID'),
      secretAccessKey: required('S3_SECRET_ACCESS_KEY'),
      forcePathStyle: optionalBool('S3_FORCE_PATH_STYLE', true),
    },
    smtp: {
      host: required('SMTP_HOST'),
      port: Number.parseInt(optional('SMTP_PORT', '587'), 10),
      secure: optionalBool('SMTP_SECURE', false),
      user: optional('SMTP_USER', ''),
      pass: optional('SMTP_PASS', ''),
      fromAddress: optional('SMTP_FROM_ADDRESS', 'no-reply@localhost'),
      fromName: optional('SMTP_FROM_NAME', 'Hasan Journal'),
    },
    allowDemoSeed: optionalBool('ALLOW_DEMO_SEED', false) && !isProduction,
  }
}

export type Env = ReturnType<typeof loadEnv>

let cached: Env | undefined

export function getEnv(): Env {
  if (!cached) {
    cached = loadEnv()
  }
  return cached
}
