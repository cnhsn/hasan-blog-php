import { RichText as PayloadRichText } from '@payloadcms/richtext-lexical/react'

import { richTextConverters } from './converters'

type LexicalData = Parameters<typeof PayloadRichText>[0]['data']

export function RichText({ data, className }: { data: LexicalData; className?: string }) {
  return (
    <PayloadRichText
      data={data}
      converters={richTextConverters}
      disableContainer
      className={className}
    />
  )
}
