import { describe, expect, it } from 'vitest'

import { findUnsafeLinks, isSafeExternalUrl, lexicalToPlainText } from '@/lib/lexical'
import { createPreviewToken, verifyPreviewToken } from '@/lib/preview'
import { calculateReadingTimeMinutes } from '@/lib/readingTime'
import { slugify, validateInternalPath, validateSlug } from '@/lib/slug'
import { validateHttpsUrl, validateHttpUrl } from '@/lib/url'
import { escapeXml } from '@/lib/xml'

describe('slug handling', () => {
  it('generates kebab-case ASCII slugs from titles', () => {
    expect(slugify('Hello, World! 2026')).toBe('hello-world-2026')
    expect(slugify('  Trailing and leading  ')).toBe('trailing-and-leading')
    expect(slugify('Türkçe Başlık')).toBe('turkce-baslik')
    expect(slugify('Straße')).toBe('strasse')
  })

  it('accepts valid slugs and rejects malformed ones', () => {
    expect(validateSlug('a-valid-slug-1')).toBe(true)
    expect(validateSlug('')).not.toBe(true)
    expect(validateSlug('Has Capitals')).not.toBe(true)
    expect(validateSlug('double--hyphen')).not.toBe(true)
    expect(validateSlug('-leading')).not.toBe(true)
    expect(validateSlug('trailing-')).not.toBe(true)
    expect(validateSlug('a'.repeat(121))).not.toBe(true)
  })

  it('rejects reserved slugs', () => {
    for (const reserved of ['admin', 'api', 'preview', 'blog', 'projects']) {
      expect(validateSlug(reserved)).not.toBe(true)
    }
  })
})

describe('internal path validation (redirects)', () => {
  it('accepts internal absolute paths', () => {
    expect(validateInternalPath('/blog/old-slug')).toBe(true)
    expect(validateInternalPath('/projects/thing')).toBe(true)
  })

  it('rejects protocol-relative, external and script targets', () => {
    expect(validateInternalPath('//evil.example.com')).not.toBe(true)
    expect(validateInternalPath('https://evil.example.com')).not.toBe(true)
    expect(validateInternalPath('javascript:alert(1)')).not.toBe(true)
    expect(validateInternalPath('relative/path')).not.toBe(true)
  })

  it('rejects reserved app path segments', () => {
    expect(validateInternalPath('/admin')).not.toBe(true)
    expect(validateInternalPath('/api/users')).not.toBe(true)
    expect(validateInternalPath('/preview')).not.toBe(true)
  })
})

describe('url field validation', () => {
  it('allows http and https for project links, rejects other schemes', () => {
    expect(validateHttpUrl('https://example.com')).toBe(true)
    expect(validateHttpUrl('http://example.com')).toBe(true)
    expect(validateHttpUrl('javascript:alert(1)')).not.toBe(true)
    expect(validateHttpUrl('ftp://example.com')).not.toBe(true)
    expect(validateHttpUrl('not a url')).not.toBe(true)
    expect(validateHttpUrl('')).toBe(true)
  })

  it('requires https for social links', () => {
    expect(validateHttpsUrl('https://example.com')).toBe(true)
    expect(validateHttpsUrl('http://example.com')).not.toBe(true)
  })
})

describe('lexical helpers', () => {
  const doc = {
    root: {
      children: [
        { type: 'paragraph', children: [{ type: 'text', text: 'Hello world' }] },
        {
          type: 'block',
          fields: { blockType: 'Code', language: 'ts', code: 'const answer = 42' },
        },
        {
          type: 'paragraph',
          children: [
            {
              type: 'link',
              fields: { linkType: 'custom', url: 'javascript:alert(1)', newTab: false },
              children: [{ type: 'text', text: 'bad link' }],
            },
            {
              type: 'link',
              fields: { linkType: 'custom', url: 'https://example.com', newTab: false },
              children: [{ type: 'text', text: 'good link' }],
            },
          ],
        },
      ],
    },
  }

  it('extracts plain text including code block contents', () => {
    const text = lexicalToPlainText(doc)
    expect(text).toContain('Hello world')
    expect(text).toContain('const answer = 42')
    // Structural keys must not leak into the text used for reading time.
    expect(text).not.toContain('paragraph')
    expect(text).not.toContain('blockType')
  })

  it('returns empty string for non-lexical values', () => {
    expect(lexicalToPlainText(null)).toBe('')
    expect(lexicalToPlainText({})).toBe('')
    expect(lexicalToPlainText('nope')).toBe('')
  })

  it('flags only unsafe link schemes', () => {
    expect(findUnsafeLinks(doc)).toEqual(['javascript:alert(1)'])
  })

  it('classifies link schemes', () => {
    expect(isSafeExternalUrl('https://example.com')).toBe(true)
    expect(isSafeExternalUrl('http://example.com')).toBe(true)
    expect(isSafeExternalUrl('mailto:someone@example.com')).toBe(true)
    expect(isSafeExternalUrl('javascript:alert(1)')).toBe(false)
    expect(isSafeExternalUrl('data:text/html;base64,PHNjcmlwdD4=')).toBe(false)
    expect(isSafeExternalUrl('//evil.example.com')).toBe(false)
  })
})

describe('reading time', () => {
  it('uses 200 words per minute with a one minute floor', () => {
    expect(calculateReadingTimeMinutes('')).toBe(1)
    expect(calculateReadingTimeMinutes('one two three')).toBe(1)
    expect(calculateReadingTimeMinutes('word '.repeat(400))).toBe(2)
    expect(calculateReadingTimeMinutes('word '.repeat(1000))).toBe(5)
  })
})

describe('xml escaping', () => {
  it('escapes all five XML entities', () => {
    expect(escapeXml(`<a href="x">Tom & Jerry's</a>`)).toBe(
      '&lt;a href=&quot;x&quot;&gt;Tom &amp; Jerry&apos;s&lt;/a&gt;',
    )
  })

  it('escapes ampersands before other entities (no double escaping)', () => {
    expect(escapeXml('&lt;')).toBe('&amp;lt;')
  })
})

describe('preview tokens', () => {
  it('round-trips a token for the exact document it was issued for', () => {
    const token = createPreviewToken({ collection: 'posts', slug: 'my-post' })
    expect(verifyPreviewToken(token, { collection: 'posts', slug: 'my-post' })).toBe(true)
  })

  it('rejects a token reused for a different document or collection', () => {
    const token = createPreviewToken({ collection: 'posts', slug: 'my-post' })
    expect(verifyPreviewToken(token, { collection: 'posts', slug: 'other-post' })).toBe(false)
    expect(verifyPreviewToken(token, { collection: 'projects', slug: 'my-post' })).toBe(false)
  })

  it('rejects tampered and malformed tokens', () => {
    const token = createPreviewToken({ collection: 'posts', slug: 'my-post' })
    const [payload, signature] = token.split('.')
    expect(verifyPreviewToken(`${payload}.${'a'.repeat(signature!.length)}`, {
      collection: 'posts',
      slug: 'my-post',
    })).toBe(false)
    expect(verifyPreviewToken('garbage', { collection: 'posts', slug: 'my-post' })).toBe(false)
    expect(verifyPreviewToken('', { collection: 'posts', slug: 'my-post' })).toBe(false)
  })

  it('rejects an expired token', () => {
    const expired = Math.floor(Date.now() / 1000) - 10
    const forgedPayload = Buffer.from(`posts:my-post:${expired}`, 'utf8').toString('base64url')
    // Even with a correctly signed payload, expiry must fail closed.
    const signed = createPreviewToken({ collection: 'posts', slug: 'my-post' })
    const signature = signed.split('.')[1]
    expect(verifyPreviewToken(`${forgedPayload}.${signature}`, {
      collection: 'posts',
      slug: 'my-post',
    })).toBe(false)
  })
})
