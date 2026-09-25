import Image from 'next/image'
import Link from 'next/link'

import type { Project } from '@/payload-types'

const LIFECYCLE_LABELS: Record<Project['lifecycleStatus'], string> = {
  idea: 'Idea',
  'in-progress': 'In progress',
  completed: 'Completed',
  archived: 'Archived',
}

export function ProjectCard({ project }: { project: Project }) {
  const cover = typeof project.cover === 'object' ? project.cover : null
  const technologies = project.technologies ?? []

  return (
    <Link href={`/projects/${project.slug}`} className="card">
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
        <span className="card-status">{LIFECYCLE_LABELS[project.lifecycleStatus]}</span>
        <h3 className="card-title">{project.title}</h3>
        <p className="card-excerpt">{project.shortDescription}</p>
        {technologies.length > 0 && (
          <div className="card-tags">
            {technologies.map((tech) => (
              <span key={tech} className="card-tag">
                {tech}
              </span>
            ))}
          </div>
        )}
      </div>
    </Link>
  )
}
