/** Validates an optional http(s)-only URL field (repositoryURL/liveURL). */
export function validateHttpUrl(value: unknown): string | true {
  if (value === undefined || value === null || value === '') return true
  if (typeof value !== 'string') return 'Must be a URL.'

  let parsed: URL
  try {
    parsed = new URL(value)
  } catch {
    return 'Must be a valid absolute URL.'
  }

  if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
    return 'Must use http or https.'
  }

  return true
}

/** Social links require https specifically (docs/CONTENT_MODEL.md SiteSettings). */
export function validateHttpsUrl(value: unknown): string | true {
  if (value === undefined || value === null || value === '') return true
  if (typeof value !== 'string') return 'Must be a URL.'

  let parsed: URL
  try {
    parsed = new URL(value)
  } catch {
    return 'Must be a valid absolute URL.'
  }

  if (parsed.protocol !== 'https:') {
    return 'Must use https.'
  }

  return true
}
