import Link from 'next/link'

export default function NotFound() {
  return (
    <main className="route-main collection-main">
      <section className="empty-state" aria-labelledby="not-found-title">
        <span className="empty-count" aria-hidden="true">
          404
        </span>
        <h1 id="not-found-title">Page not found.</h1>
        <p>The page you&rsquo;re looking for doesn&rsquo;t exist, or it moved.</p>
        <Link className="text-link" href="/">
          Back home<span aria-hidden="true">↗</span>
        </Link>
      </section>
    </main>
  )
}
