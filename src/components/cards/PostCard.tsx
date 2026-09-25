import Image from 'next/image'
import Link from 'next/link'

import { formatDate } from '@/lib/date'
import { lexicalToPlainText } from '@/lib/lexical'
import { calculateReadingTimeMinutes } from '@/lib/readingTime'
import type { Category, Post } from '@/payload-types'

export function PostCard({ post }: { post: Post }) {
  const cover = typeof post.cover === 'object' ? post.cover : null
  const categories = (post.categories ?? []).filter(
    (category): category is Category => typeof category === 'object',
  )
  const date = formatDate(post.publishedAt)
  const readingTime = calculateReadingTimeMinutes(lexicalToPlainText(post.content))

  return (
    <Link href={`/blog/${post.slug}`} className="card">
      {cover?.url && (
        <div className="card-cover">
          <Image
            src={cover.url}
            alt={cover.alt}
            fill
            sizes="(max-width: 700px) 100vw, (max-width: 1100px) 50vw, 33vw"
          />
        </div>
      )}
      <div className="card-body">
        <div className="card-meta">
          {date && <span>{date}</span>}
          <span>{readingTime} min read</span>
        </div>
        <h3 className="card-title">{post.title}</h3>
        <p className="card-excerpt">{post.excerpt}</p>
        {categories.length > 0 && (
          <div className="card-tags">
            {categories.map((category) => (
              <span key={category.id} className="card-tag">
                {category.name}
              </span>
            ))}
          </div>
        )}
      </div>
    </Link>
  )
}
