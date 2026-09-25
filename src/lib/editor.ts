import {
  BlockquoteFeature,
  BlocksFeature,
  BoldFeature,
  CodeBlock,
  FixedToolbarFeature,
  HeadingFeature,
  InlineCodeFeature,
  ItalicFeature,
  lexicalEditor,
  LinkFeature,
  OrderedListFeature,
  ParagraphFeature,
  StrikethroughFeature,
  UnderlineFeature,
  UnorderedListFeature,
  UploadFeature,
} from '@payloadcms/richtext-lexical'

/**
 * Shared rich text editor for Posts.content and Projects.caseStudy:
 * paragraphs, h2/h3, quotes, lists, links, embedded images, fenced code
 * with a language picker, and basic inline formatting — exactly the set
 * docs/CONTENT_MODEL.md calls for. Deliberately excludes tables, colors
 * and other features not in scope for V1.
 */
export function createRichTextEditor() {
  return lexicalEditor({
    features: [
      ParagraphFeature(),
      HeadingFeature({ enabledHeadingSizes: ['h2', 'h3'] }),
      BlockquoteFeature(),
      UnorderedListFeature(),
      OrderedListFeature(),
      LinkFeature({ enabledCollections: ['posts', 'projects'] }),
      UploadFeature({ enabledCollections: ['media'] }),
      BlocksFeature({ blocks: [CodeBlock()] }),
      BoldFeature(),
      ItalicFeature(),
      UnderlineFeature(),
      StrikethroughFeature(),
      InlineCodeFeature(),
      FixedToolbarFeature(),
    ],
  })
}
