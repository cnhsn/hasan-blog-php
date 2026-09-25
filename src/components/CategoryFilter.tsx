import Link from 'next/link'

import type { Category } from '@/payload-types'

export function CategoryFilter({
  categories,
  activeSlug,
}: {
  categories: Category[]
  activeSlug?: string
}) {
  if (categories.length === 0) return null

  return (
    <div className="filter-bar" role="group" aria-label="Filter by category">
      <Link href="/blog" aria-current={!activeSlug ? 'true' : undefined} className="filter-chip">
        All
      </Link>
      {categories.map((category) => (
        <Link
          key={category.id}
          href={`/blog?category=${category.slug}`}
          aria-current={activeSlug === category.slug ? 'true' : undefined}
          className="filter-chip"
        >
          {category.name}
        </Link>
      ))}
    </div>
  )
}
