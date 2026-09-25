/**
 * Minimal, dependency-free helpers for walking a Lexical serialized
 * document (the JSON shape Payload's richtext-lexical stores). We only
 * need plain-text extraction (reading time) and link-scheme validation
 * (defense in depth alongside the frontend renderer's own check in
 * src/components/richtext/converters.tsx).
 */

type LexicalNode = {
  children?: LexicalNode[]
  fields?: Record<string, unknown>
  text?: string
  type?: string
}

type LexicalDocument = {
  root?: LexicalNode
}

function isLexicalDocument(value: unknown): value is LexicalDocument {
  return Boolean(value) && typeof value === 'object' && value !== null && 'root' in value
}

export function lexicalToPlainText(doc: unknown): string {
  if (!isLexicalDocument(doc) || !doc.root) return ''
  const parts: string[] = []

  const walk = (node: LexicalNode) => {
    if (typeof node.text === 'string') {
      parts.push(node.text)
    }
    if (node.type === 'block' && node.fields && typeof node.fields.code === 'string') {
      parts.push(node.fields.code)
    }
    if (Array.isArray(node.children)) {
      for (const child of node.children) walk(child)
    }
  }

  walk(doc.root)
  return parts.join(' ')
}

export function isSafeExternalUrl(url: string): boolean {
  try {
    const parsed = new URL(url)
    return parsed.protocol === 'https:' || parsed.protocol === 'http:' || parsed.protocol === 'mailto:'
  } catch {
    return false
  }
}

/**
 * Walks all link/autolink nodes in a Lexical document and returns the
 * raw `url` field of any external link that fails {@link isSafeExternalUrl}.
 * Internal links (linkType === 'internal') reference a real Payload
 * document by id and are not user-supplied strings, so they are skipped.
 */
export function findUnsafeLinks(doc: unknown): string[] {
  if (!isLexicalDocument(doc) || !doc.root) return []
  const unsafe: string[] = []

  const walk = (node: LexicalNode) => {
    if ((node.type === 'link' || node.type === 'autolink') && node.fields) {
      const linkType = node.fields.linkType
      const url = node.fields.url
      if (linkType !== 'internal' && typeof url === 'string' && !isSafeExternalUrl(url)) {
        unsafe.push(url)
      }
    }
    if (Array.isArray(node.children)) {
      for (const child of node.children) walk(child)
    }
  }

  walk(doc.root)
  return unsafe
}
