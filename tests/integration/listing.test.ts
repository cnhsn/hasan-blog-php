import type { Payload } from 'payload'
import { beforeAll, beforeEach, describe, expect, it } from 'vitest'

import { listPosts, PAGE_SIZE } from '@/lib/queries/posts'

import { getTestPayload, lexicalParagraph, resetContent } from '../helpers/payload'

/**
 * Listing behaviour behind /blog: page size, pagination, category
 * filtering and draft exclusion (docs/ACCEPTANCE.md A16).
 */
describe('post listing queries', () => {
  let payload: Payload
  let engineeringId: number | string
  let notesId: number | string

  beforeAll(async () => {
    payload = await getTestPayload()
  })

  beforeEach(async () => {
    await resetContent()

    const engineering = await payload.create({
      collection: 'categories',
      overrideAccess: true,
      data: { name: 'Engineering', slug: 'engineering' },
    })
    const notes = await payload.create({
      collection: 'categories',
      overrideAccess: true,
      data: { name: 'Notes', slug: 'notes' },
    })
    engineeringId = engineering.id
    notesId = notes.id

    // 11 published posts: 5 Engineering (even), 6 Notes (odd).
    for (let index = 1; index <= 11; index += 1) {
      await payload.create({
        collection: 'posts',
        overrideAccess: true,
        data: {
          title: `Post ${index}`,
          slug: `post-${index}`,
          excerpt: `Excerpt ${index}`,
          content: lexicalParagraph(`Body ${index}`),
          authorName: 'Hasan',
          categories: [index % 2 === 0 ? engineeringId : notesId],
          _status: 'published',
        },
      })
    }

    await payload.create({
      collection: 'posts',
      overrideAccess: true,
      data: {
        title: 'Hidden draft',
        slug: 'hidden-draft',
        excerpt: 'Should never be listed',
        content: lexicalParagraph('Draft'),
        authorName: 'Hasan',
        categories: [engineeringId],
        _status: 'draft',
      },
    })
  })

  it('fills the first page to the configured page size', async () => {
    const result = await listPosts({ page: 1 })
    expect(PAGE_SIZE).toBe(9)
    expect(result.docs).toHaveLength(9)
    expect(result.totalDocs).toBe(11)
    expect(result.totalPages).toBe(2)
    expect(result.page).toBe(1)
  })

  it('returns the remaining posts on the last page', async () => {
    const result = await listPosts({ page: 2 })
    expect(result.docs).toHaveLength(2)
    expect(result.page).toBe(2)
  })

  it('returns no documents past the last page without failing', async () => {
    const result = await listPosts({ page: 99 })
    expect(result.docs).toHaveLength(0)
    expect(result.totalPages).toBe(2)
  })

  it('never returns the same post on two pages', async () => {
    const first = await listPosts({ page: 1 })
    const second = await listPosts({ page: 2 })
    const ids = [...first.docs, ...second.docs].map((doc) => doc.id)
    expect(new Set(ids).size).toBe(ids.length)
    expect(ids).toHaveLength(11)
  })

  it('filters by category and excludes drafts from the counts', async () => {
    const engineering = await listPosts({ page: 1, categorySlug: 'engineering' })
    expect(engineering.totalDocs).toBe(5)

    const notes = await listPosts({ page: 1, categorySlug: 'notes' })
    expect(notes.totalDocs).toBe(6)

    for (const doc of [...engineering.docs, ...notes.docs]) {
      expect(doc.slug).not.toBe('hidden-draft')
    }
  })

  it('returns an empty result for a category with no published posts', async () => {
    await payload.create({
      collection: 'categories',
      overrideAccess: true,
      data: { name: 'Empty', slug: 'empty' },
    })
    const result = await listPosts({ page: 1, categorySlug: 'empty' })
    expect(result.docs).toHaveLength(0)
    expect(result.totalDocs).toBe(0)
  })
})
