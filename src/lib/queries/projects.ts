import { getPayloadClient } from '@/lib/payload'
import type { ReadOptions } from '@/lib/queries/posts'
import type { Project } from '@/payload-types'

export const PAGE_SIZE = 9

export async function listProjects({
  page = 1,
}: {
  page?: number
}): Promise<{ docs: Project[]; totalPages: number; totalDocs: number; page: number }> {
  const payload = await getPayloadClient()

  const result = await payload.find({
    collection: 'projects',
    overrideAccess: false,
    page,
    limit: PAGE_SIZE,
    sort: '-startDate',
    depth: 1,
  })

  return {
    docs: result.docs,
    totalPages: result.totalPages,
    totalDocs: result.totalDocs,
    page: result.page ?? 1,
  }
}

export async function getProjectBySlug(
  slug: string,
  options?: ReadOptions,
): Promise<Project | null> {
  const payload = await getPayloadClient()

  const result = await payload.find({
    collection: 'projects',
    overrideAccess: false,
    user: options?.user ?? undefined,
    draft: options?.draft ?? false,
    where: { slug: { equals: slug } },
    limit: 1,
    depth: 2,
  })

  return result.docs[0] ?? null
}
