import { draftMode } from 'next/headers'
import { notFound, permanentRedirect } from 'next/navigation'
import type { Metadata } from 'next'

import { MediaImage } from '@/components/MediaImage'
import { PostCard } from '@/components/cards/PostCard'
import { PreviewBanner } from '@/components/PreviewBanner'
import { RichText } from '@/components/richtext/RichText'
import { getOwnerUser } from '@/lib/auth'
import { formatDate } from '@/lib/date'
import { getEnv } from '@/lib/env'
import { lexicalToPlainText } from '@/lib/lexical'
import { getPostBySlug, listRelatedPosts } from '@/lib/queries/posts'
import { resolveRedirect } from '@/lib/queries/redirects'
import { calculateReadingTimeMinutes } from '@/lib/readingTime'
import type { Category, Tag } from '@/payload-types'

export const dynamic = 'force-dynamic'

type Params = { slug: string }

export async function generateMetadata({
  params,
}: {
  params: Promise<Params>
}): Promise<Metadata> {
  const { slug } = await params
  const post = await getPostBySlug(slug)
  const env = getEnv()

  if (!post) return {}

  const title = post.seo?.title || post.title
  const description = post.seo?.description || post.excerpt
  const shareImage = typeof post.seo?.shareImage === 'object' ? post.seo.shareImage : null
  const cover = typeof post.cover === 'object' ? post.cover : null
  const image = shareImage?.url ?? cover?.url ?? undefined

  return {
    title,
    description,
    alternates: { canonical: `${env.publicSiteUrl}/blog/${slug}` },
    openGraph: {
      title,
      description,
      type: 'article',
      images: image ? [image] : undefined,
    },
  }
}

export default async function PostPage({ params }: { params: Promise<Params> }) {
  const { slug } = await params

  // Draft mode alone is not enough: the owner session is re-verified on
  // every draft render, so a stale draft-mode cookie stops working the
  // moment the session ends (docs/SECURITY.md).
  const { isEnabled: draftModeEnabled } = await draftMode()
  const previewUser = draftModeEnabled ? await getOwnerUser() : null
  const isPreview = Boolean(previewUser)

  const post = await getPostBySlug(slug, isPreview ? { draft: true, user: previewUser } : undefined)

  if (!post) {
    const target = await resolveRedirect(`/blog/${slug}`)
    if (target) permanentRedirect(target)
    notFound()
  }

  const cover = typeof post.cover === 'object' ? post.cover : null
  const categories = (post.categories ?? []).filter(
    (category): category is Category => typeof category === 'object',
  )
  const tags = (post.tags ?? []).filter((tag): tag is Tag => typeof tag === 'object')
  const date = formatDate(post.publishedAt)
  const readingTime = calculateReadingTimeMinutes(lexicalToPlainText(post.content))
  const relatedPosts = await listRelatedPosts(post)

  return (
    <main className="route-main detail-main">
      {isPreview && <PreviewBanner exitTo={`/blog/${slug}`} />}
      <header className="detail-header motion-in">
        <div className="detail-meta">
          {date && <span>{date}</span>}
          <span>{readingTime} min read</span>
          <span>{post.authorName}</span>
        </div>
        <h1 className="detail-title">{post.title}</h1>
        <p className="detail-excerpt">{post.excerpt}</p>
        {categories.length > 0 && (
          <div className="detail-tags">
            {categories.map((category) => (
              <span key={category.id} className="card-tag">
                {category.name}
              </span>
            ))}
          </div>
        )}
      </header>

      {cover && (
        <div className="detail-cover">
          <MediaImage media={cover} sizes="100vw" priority />
        </div>
      )}

      <RichText data={post.content} className="detail-body" />

      {tags.length > 0 && (
        <div className="detail-tags">
          {tags.map((tag) => (
            <span key={tag.id} className="card-tag">
              {tag.name}
            </span>
          ))}
        </div>
      )}

      {relatedPosts.length > 0 && (
        <section aria-labelledby="related-heading">
          <h2 id="related-heading" className="section-heading">
            Related posts
          </h2>
          <div className="card-grid">
            {relatedPosts.map((related) => (
              <PostCard key={related.id} post={related} />
            ))}
          </div>
        </section>
      )}
    </main>
  )
}
