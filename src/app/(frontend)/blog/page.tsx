import Link from 'next/link'
import type { Metadata } from 'next'

import { CategoryFilter } from '@/components/CategoryFilter'
import { PostCard } from '@/components/cards/PostCard'
import { Pagination } from '@/components/Pagination'
import { getEnv } from '@/lib/env'
import { listPosts } from '@/lib/queries/posts'
import { listCategories } from '@/lib/queries/taxonomy'

export const dynamic = 'force-dynamic'

export function generateMetadata(): Metadata {
  const env = getEnv()
  return {
    title: 'Blog',
    description: 'Thoughts on technology, the things I learn, and life between projects.',
    alternates: { canonical: `${env.publicSiteUrl}/blog` },
  }
}

function parsePage(value: string | undefined): number {
  const parsed = Number.parseInt(value ?? '1', 10)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : 1
}

export default async function BlogPage({
  searchParams,
}: {
  searchParams: Promise<{ category?: string; page?: string }>
}) {
  const params = await searchParams
  const page = parsePage(params.page)
  const categorySlug = params.category

  const [{ docs, totalPages }, categories] = await Promise.all([
    listPosts({ page, categorySlug }),
    listCategories(),
  ])

  return (
    <main className="route-main collection-main">
      <div className="collection-heading motion-in">
        <div>
          <p className="eyebrow">01 / THE JOURNAL</p>
          <h1>
            Notes &amp; <em>ideas.</em>
          </h1>
        </div>
        <p className="collection-summary">
          Thoughts on technology, the things I learn, and life between projects.
        </p>
      </div>

      <CategoryFilter categories={categories} activeSlug={categorySlug} />

      {docs.length === 0 ? (
        <section className="empty-state" aria-labelledby="empty-title">
          <span className="empty-count" aria-hidden="true">
            00
          </span>
          <h2 id="empty-title">No posts published yet.</h2>
          <p>This journal is waiting for its first entry. Check back for new writing.</p>
          <Link className="text-link" href="/projects">
            Explore projects<span aria-hidden="true">↗</span>
          </Link>
        </section>
      ) : (
        <>
          <div className="card-grid">
            {docs.map((post) => (
              <PostCard key={post.id} post={post} />
            ))}
          </div>
          <Pagination basePath="/blog" page={page} totalPages={totalPages} category={categorySlug} />
        </>
      )}
    </main>
  )
}
