export const RESERVED_SLUGS = new Set([
  'admin',
  'api',
  'preview',
  'blog',
  'projects',
  'sitemap.xml',
  'robots.txt',
  'rss.xml',
  '_next',
  'favicon.ico',
  'health',
])

const SLUG_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

export function slugify(input: string): string {
  return input
    .normalize('NFKD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 120)
}

export function validateSlug(value: unknown): string | true {
  if (typeof value !== 'string' || value.length === 0) {
    return 'Slug is required.'
  }
  if (value.length > 120) {
    return 'Slug must be 120 characters or fewer.'
  }
  if (!SLUG_PATTERN.test(value)) {
    return 'Slug must be lowercase ASCII letters, numbers and single hyphens only (e.g. "my-post-title").'
  }
  if (RESERVED_SLUGS.has(value)) {
    return `"${value}" is a reserved path and cannot be used as a slug.`
  }
  return true
}

const RESERVED_TOP_LEVEL_SEGMENTS = new Set(['admin', 'api', 'preview'])

/**
 * Validates a redirect `sourcePath`/`targetPath`: must be a single
 * internal absolute path. Rejects protocol-relative URLs (`//host/...`),
 * external schemes, `javascript:`, and the reserved app path segments.
 */
export function validateInternalPath(value: unknown): string | true {
  if (typeof value !== 'string' || value.length === 0) {
    return 'Path is required.'
  }
  if (!value.startsWith('/') || value.startsWith('//')) {
    return 'Path must be an internal absolute path starting with a single "/".'
  }
  if (value.includes('://') || value.toLowerCase().includes('javascript:')) {
    return 'Path must not reference an external URL or script scheme.'
  }
  const firstSegment = value.split('/')[1]
  if (firstSegment && RESERVED_TOP_LEVEL_SEGMENTS.has(firstSegment)) {
    return `"/${firstSegment}" is a reserved path and cannot be used here.`
  }
  return true
}
