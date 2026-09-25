import type { MetadataRoute } from 'next'

import { getEnv } from '@/lib/env'
import { getPayloadClient } from '@/lib/payload'

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const env = getEnv()
  const payload = await getPayloadClient()

  const [posts, projects] = await Promise.all([
    payload.find({ collection: 'posts', overrideAccess: false, limit: 1000, depth: 0 }),
    payload.find({ collection: 'projects', overrideAccess: false, limit: 1000, depth: 0 }),
  ])

  const staticRoutes: MetadataRoute.Sitemap = ['', '/blog', '/projects'].map((path) => ({
    url: `${env.publicSiteUrl}${path}`,
  }))

  const postRoutes: MetadataRoute.Sitemap = posts.docs.map((post) => ({
    url: `${env.publicSiteUrl}/blog/${post.slug}`,
    lastModified: post.updatedAt ?? undefined,
  }))

  const projectRoutes: MetadataRoute.Sitemap = projects.docs.map((project) => ({
    url: `${env.publicSiteUrl}/projects/${project.slug}`,
    lastModified: project.updatedAt ?? undefined,
  }))

  return [...staticRoutes, ...postRoutes, ...projectRoutes]
}
