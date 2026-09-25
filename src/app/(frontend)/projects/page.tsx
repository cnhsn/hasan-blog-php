import Link from 'next/link'
import type { Metadata } from 'next'

import { ProjectCard } from '@/components/cards/ProjectCard'
import { Pagination } from '@/components/Pagination'
import { getEnv } from '@/lib/env'
import { listProjects } from '@/lib/queries/projects'

export const dynamic = 'force-dynamic'

export function generateMetadata(): Metadata {
  const env = getEnv()
  return {
    title: 'Projects',
    description: 'A place for experiments, side projects, and ideas turned into something useful.',
    alternates: { canonical: `${env.publicSiteUrl}/projects` },
  }
}

function parsePage(value: string | undefined): number {
  const parsed = Number.parseInt(value ?? '1', 10)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : 1
}

export default async function ProjectsPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>
}) {
  const params = await searchParams
  const page = parsePage(params.page)
  const { docs, totalPages } = await listProjects({ page })

  return (
    <main className="route-main collection-main">
      <div className="collection-heading motion-in">
        <div>
          <p className="eyebrow">02 / THE WORKBENCH</p>
          <h1>
            Things I <em>build.</em>
          </h1>
        </div>
        <p className="collection-summary">
          A place for experiments, side projects, and ideas turned into something useful.
        </p>
      </div>

      {docs.length === 0 ? (
        <section className="empty-state" aria-labelledby="empty-title">
          <span className="empty-count" aria-hidden="true">
            00
          </span>
          <h2 id="empty-title">No projects published yet.</h2>
          <p>The workbench is ready. Projects will appear here as they&rsquo;re added.</p>
          <Link className="text-link" href="/blog">
            Visit the blog<span aria-hidden="true">↗</span>
          </Link>
        </section>
      ) : (
        <>
          <div className="card-grid">
            {docs.map((project) => (
              <ProjectCard key={project.id} project={project} />
            ))}
          </div>
          <Pagination basePath="/projects" page={page} totalPages={totalPages} />
        </>
      )}
    </main>
  )
}
