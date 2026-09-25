import Link from 'next/link'

function buildHref(basePath: string, page: number, category?: string): string {
  const params = new URLSearchParams()
  if (category) params.set('category', category)
  if (page > 1) params.set('page', String(page))
  const qs = params.toString()
  return qs ? `${basePath}?${qs}` : basePath
}

export function Pagination({
  basePath,
  page,
  totalPages,
  category,
}: {
  basePath: string
  page: number
  totalPages: number
  category?: string
}) {
  if (totalPages <= 1) return null

  const pages = Array.from({ length: totalPages }, (_, index) => index + 1)

  return (
    <nav className="pagination" aria-label="Pagination">
      {page > 1 ? (
        <Link href={buildHref(basePath, page - 1, category)} rel="prev">
          ‹
        </Link>
      ) : (
        <span className="disabled">‹</span>
      )}
      {pages.map((p) => (
        <Link
          key={p}
          href={buildHref(basePath, p, category)}
          aria-current={p === page ? 'page' : undefined}
        >
          {p}
        </Link>
      ))}
      {page < totalPages ? (
        <Link href={buildHref(basePath, page + 1, category)} rel="next">
          ›
        </Link>
      ) : (
        <span className="disabled">›</span>
      )}
    </nav>
  )
}
