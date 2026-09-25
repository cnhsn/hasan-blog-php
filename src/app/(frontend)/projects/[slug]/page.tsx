import { draftMode } from 'next/headers'
import { notFound, permanentRedirect } from 'next/navigation'
import type { Metadata } from 'next'

import { MediaImage } from '@/components/MediaImage'
import { PreviewBanner } from '@/components/PreviewBanner'
import { RichText } from '@/components/richtext/RichText'
import { getOwnerUser } from '@/lib/auth'
import { getEnv } from '@/lib/env'
import { getProjectBySlug } from '@/lib/queries/projects'
import { resolveRedirect } from '@/lib/queries/redirects'
import type { Media } from '@/payload-types'

export const dynamic = 'force-dynamic'

type Params = { slug: string }

const LIFECYCLE_LABELS: Record<string, string> = {
  idea: 'Idea',
  'in-progress': 'In progress',
  completed: 'Completed',
  archived: 'Archived',
}

export async function generateMetadata({
  params,
}: {
  params: Promise<Params>
}): Promise<Metadata> {
  const { slug } = await params
  const project = await getProjectBySlug(slug)
  const env = getEnv()

  if (!project) return {}

  const title = project.seo?.title || project.title
  const description = project.seo?.description || project.shortDescription
  const shareImage = typeof project.seo?.shareImage === 'object' ? project.seo.shareImage : null
  const cover = typeof project.cover === 'object' ? project.cover : null
  const image = shareImage?.url ?? cover?.url ?? undefined

  return {
    title,
    description,
    alternates: { canonical: `${env.publicSiteUrl}/projects/${slug}` },
    openGraph: {
      title,
      description,
      type: 'article',
      images: image ? [image] : undefined,
    },
  }
}

export default async function ProjectPage({ params }: { params: Promise<Params> }) {
  const { slug } = await params

  const { isEnabled: draftModeEnabled } = await draftMode()
  const previewUser = draftModeEnabled ? await getOwnerUser() : null
  const isPreview = Boolean(previewUser)

  const project = await getProjectBySlug(
    slug,
    isPreview ? { draft: true, user: previewUser } : undefined,
  )

  if (!project) {
    const target = await resolveRedirect(`/projects/${slug}`)
    if (target) permanentRedirect(target)
    notFound()
  }

  const cover = typeof project.cover === 'object' ? project.cover : null
  const technologies = project.technologies ?? []
  type GalleryItem = { media: Media; caption?: string | null; id?: string | null }
  const gallery = (project.gallery ?? []).filter(
    (item): item is GalleryItem => typeof item.media === 'object',
  )

  return (
    <main className="route-main detail-main">
      {isPreview && <PreviewBanner exitTo={`/projects/${slug}`} />}
      <header className="detail-header motion-in">
        <div className="detail-meta">
          <span>{LIFECYCLE_LABELS[project.lifecycleStatus]}</span>
        </div>
        <h1 className="detail-title">{project.title}</h1>
        <p className="detail-excerpt">{project.shortDescription}</p>
        {technologies.length > 0 && (
          <ul className="project-tech-list">
            {technologies.map((tech) => (
              <li key={tech} className="card-tag">
                {tech}
              </li>
            ))}
          </ul>
        )}
        {(project.repositoryURL || project.liveURL) && (
          <div className="project-links">
            {project.repositoryURL && (
              <a
                className="project-link-button"
                href={project.repositoryURL}
                target="_blank"
                rel="noopener noreferrer"
              >
                Repository<span aria-hidden="true">↗</span>
              </a>
            )}
            {project.liveURL && (
              <a
                className="project-link-button"
                href={project.liveURL}
                target="_blank"
                rel="noopener noreferrer"
              >
                Live demo<span aria-hidden="true">↗</span>
              </a>
            )}
          </div>
        )}
      </header>

      {cover && (
        <div className="detail-cover">
          <MediaImage media={cover} sizes="100vw" priority />
        </div>
      )}

      <RichText data={project.caseStudy} className="detail-body" />

      {project.problem && (
        <section>
          <h2 className="section-heading">Problem</h2>
          <RichText data={project.problem} className="detail-body" />
        </section>
      )}
      {project.approach && (
        <section>
          <h2 className="section-heading">Approach</h2>
          <RichText data={project.approach} className="detail-body" />
        </section>
      )}
      {project.results && (
        <section>
          <h2 className="section-heading">Results</h2>
          <RichText data={project.results} className="detail-body" />
        </section>
      )}

      {gallery.length > 0 && (
        <section>
          <h2 className="section-heading">Gallery</h2>
          <div className="gallery-grid">
            {gallery.map((item, index) => (
              <figure key={item.id ?? index}>
                <div className="gallery-item">
                  <MediaImage media={item.media} sizes="(max-width: 700px) 100vw, 50vw" />
                </div>
                {item.caption && <figcaption className="gallery-caption">{item.caption}</figcaption>}
              </figure>
            ))}
          </div>
        </section>
      )}
    </main>
  )
}
