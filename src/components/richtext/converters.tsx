import {
  type JSXConvertersFunction,
  LinkJSXConverter,
} from '@payloadcms/richtext-lexical/react'
import type { SerializedBlockNode, SerializedLinkNode } from '@payloadcms/richtext-lexical'

import { isSafeExternalUrl } from '@/lib/lexical'

import { CodeBlock } from './CodeBlock'

type CodeBlockFields = {
  blockName?: null | string
  blockType: 'Code'
  code: string
  language?: string
}

function internalDocToHref({ linkNode }: { linkNode: SerializedLinkNode }): string {
  const doc = linkNode.fields.doc
  if (!doc || !doc.value || typeof doc.value !== 'object') return '#'
  const slug = 'slug' in doc.value ? doc.value.slug : undefined
  if (typeof slug !== 'string' || slug.length === 0) return '#'
  if (doc.relationTo === 'posts') return `/blog/${slug}`
  if (doc.relationTo === 'projects') return `/projects/${slug}`
  return '#'
}

/**
 * Merged on top of the default converters below: defense in depth
 * against an unsafe link scheme slipping through (the collection-level
 * save-time validator in src/hooks/validateContentLinks.ts is the
 * primary check).
 */
function safeHref(url: string | undefined): string {
  if (!url) return '#'
  return isSafeExternalUrl(url) ? url : '#'
}

export const richTextConverters: JSXConvertersFunction = ({ defaultConverters }) => ({
  ...defaultConverters,
  ...LinkJSXConverter({ internalDocToHref }),
  link: ({ node, nodesToJSX }) => {
    const children = nodesToJSX({ nodes: node.children })
    const rel = node.fields.newTab ? 'noopener noreferrer' : undefined
    const target = node.fields.newTab ? '_blank' : undefined
    const href =
      node.fields.linkType === 'internal' ? internalDocToHref({ linkNode: node }) : safeHref(node.fields.url)
    return (
      <a href={href} rel={rel} target={target}>
        {children}
      </a>
    )
  },
  autolink: ({ node, nodesToJSX }) => {
    const children = nodesToJSX({ nodes: node.children })
    const rel = node.fields.newTab ? 'noopener noreferrer' : undefined
    const target = node.fields.newTab ? '_blank' : undefined
    return (
      <a href={safeHref(node.fields.url)} rel={rel} target={target}>
        {children}
      </a>
    )
  },
  blocks: {
    // Slug and field names come from @payloadcms/richtext-lexical's premade
    // CodeBlock (src/lib/editor.ts): { language: select, code: code }.
    Code: ({ node }: { node: SerializedBlockNode<CodeBlockFields> }) => (
      <CodeBlock language={node.fields.language} code={node.fields.code} />
    ),
  },
})
