import { getPayloadClient } from '@/lib/payload'
import type { Post } from '@/payload-types'
import type { TypedUser, Where } from 'payload'

export const PAGE_SIZE = 9

/**
 * Draft reads are only ever performed with a verified owner user
 * attached — `overrideAccess` stays false so Payload's own access
 * control is still the gate (docs/SECURITY.md "Draft isolation").
 */
export type ReadOptions = {
  draft?: boolean
  user?: TypedUser | null
}

export async function listPosts({
  page = 1,
  categorySlug,
}: {
  page?: number
  categorySlug?: string
}): Promise<{ docs: Post[]; totalPages: number; totalDocs: number; page: number }> {
  const payload = await getPayloadClient()

  const where: Where = {}
  if (categorySlug) {
    where['categories.slug'] = { equals: categorySlug }
  }

  const result = await payload.find({
    collection: 'posts',
    overrideAccess: false,
    where,
    page,
    limit: PAGE_SIZE,
    sort: '-publishedAt',
    depth: 1,
  })

  return {
    docs: result.docs,
    totalPages: result.totalPages,
    totalDocs: result.totalDocs,
    page: result.page ?? 1,
  }
}

export async function getPostBySlug(slug: string, options?: ReadOptions): Promise<Post | null> {
  const payload = await getPayloadClient()

  const result = await payload.find({
    collection: 'posts',
    overrideAccess: false,
    user: options?.user ?? undefined,
    draft: options?.draft ?? false,
    where: { slug: { equals: slug } },
    limit: 1,
    depth: 2,
  })

  return result.docs[0] ?? null
}

export async function listRelatedPosts(post: Post, limit = 3): Promise<Post[]> {
  const payload = await getPayloadClient()

  const categoryIds = (post.categories ?? [])
    .map((c) => (typeof c === 'object' ? c.id : c))
    .filter((id): id is number => typeof id === 'number')
  const tagIds = (post.tags ?? [])
    .map((t) => (typeof t === 'object' ? t.id : t))
    .filter((id): id is number => typeof id === 'number')

  if (categoryIds.length === 0 && tagIds.length === 0) return []

  const or: Where[] = []
  if (categoryIds.length > 0) or.push({ categories: { in: categoryIds } })
  if (tagIds.length > 0) or.push({ tags: { in: tagIds } })

  const result = await payload.find({
    collection: 'posts',
    overrideAccess: false,
    where: {
      and: [{ or }, { id: { not_equals: post.id } }],
    },
    limit,
    sort: '-publishedAt',
    depth: 1,
  })

  return result.docs
}
