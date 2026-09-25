import type { Payload, TypedUser } from 'payload'
import { beforeAll, beforeEach, describe, expect, it } from 'vitest'

import { getTestOwner, getTestPayload, lexicalParagraph, resetContent } from '../helpers/payload'

/**
 * Draft isolation and anonymous authorization (docs/ACCEPTANCE.md
 * A04/A05/A07/A09). "Anonymous" here means a Local API call with
 * `overrideAccess: false` and no user — exactly how the public pages
 * read content.
 */
describe('draft isolation and access control', () => {
  let payload: Payload
  let owner: TypedUser

  beforeAll(async () => {
    payload = await getTestPayload()
    owner = await getTestOwner()
  })

  beforeEach(async () => {
    await resetContent()
  })

  async function createPost(overrides: Record<string, unknown> = {}) {
    return payload.create({
      collection: 'posts',
      overrideAccess: true,
      data: {
        title: 'A draft post',
        slug: 'a-draft-post',
        excerpt: 'Excerpt for the draft post.',
        content: lexicalParagraph('Draft body text.'),
        authorName: 'Hasan',
        _status: 'draft',
        ...overrides,
      },
    })
  }

  it('hides drafts from anonymous list and detail reads', async () => {
    await createPost()

    const anonList = await payload.find({ collection: 'posts', overrideAccess: false })
    expect(anonList.docs).toHaveLength(0)
    expect(anonList.totalDocs).toBe(0)

    const anonBySlug = await payload.find({
      collection: 'posts',
      overrideAccess: false,
      where: { slug: { equals: 'a-draft-post' } },
    })
    expect(anonBySlug.docs).toHaveLength(0)
  })

  it('shows drafts to the authenticated owner', async () => {
    await createPost()

    const ownerList = await payload.find({
      collection: 'posts',
      overrideAccess: false,
      user: owner,
    })
    expect(ownerList.docs).toHaveLength(1)
  })

  it('exposes a post publicly only once published', async () => {
    const post = await createPost()

    let anon = await payload.find({ collection: 'posts', overrideAccess: false })
    expect(anon.docs).toHaveLength(0)

    await payload.update({
      collection: 'posts',
      id: post.id,
      overrideAccess: true,
      data: { _status: 'published' },
    })

    anon = await payload.find({ collection: 'posts', overrideAccess: false })
    expect(anon.docs).toHaveLength(1)
    expect(anon.docs[0]?.slug).toBe('a-draft-post')
  })

  it('keeps serving the last published revision while a newer draft exists', async () => {
    const post = await createPost({ title: 'Published title', _status: 'published' })

    await payload.update({
      collection: 'posts',
      id: post.id,
      overrideAccess: true,
      draft: true,
      data: { title: 'Unpublished newer title' },
    })

    const anon = await payload.find({ collection: 'posts', overrideAccess: false })
    expect(anon.docs[0]?.title).toBe('Published title')

    const ownerDraft = await payload.find({
      collection: 'posts',
      overrideAccess: false,
      user: owner,
      draft: true,
    })
    expect(ownerDraft.docs[0]?.title).toBe('Unpublished newer title')
  })

  it('removes an unpublished post from public reads', async () => {
    const post = await createPost({ _status: 'published' })
    expect((await payload.find({ collection: 'posts', overrideAccess: false })).docs).toHaveLength(1)

    await payload.update({
      collection: 'posts',
      id: post.id,
      overrideAccess: true,
      data: { _status: 'draft' },
    })

    expect((await payload.find({ collection: 'posts', overrideAccess: false })).docs).toHaveLength(0)
  })

  it('denies anonymous writes to content collections', async () => {
    await expect(
      payload.create({
        collection: 'posts',
        overrideAccess: false,
        data: {
          title: 'Anonymous post',
          slug: 'anonymous-post',
          excerpt: 'Nope.',
          content: lexicalParagraph('Nope.'),
          authorName: 'Anonymous',
        },
      }),
    ).rejects.toThrow()
  })

  it('denies anonymous reads of the users collection', async () => {
    await expect(payload.find({ collection: 'users', overrideAccess: false })).rejects.toThrow()
  })

  it('refuses to create a user outside the bootstrap CLI, even with overrideAccess', async () => {
    await expect(
      payload.create({
        collection: 'users',
        overrideAccess: true,
        data: {
          email: 'sneaky@example.invalid',
          password: 'sneaky-password',
          displayName: 'Sneaky',
          role: 'owner',
        },
      }),
    ).rejects.toThrow()
  })

  it('keeps drafts out of anonymous relationship population', async () => {
    const draft = await createPost({ slug: 'related-draft', title: 'Related draft' })
    const category = await payload.create({
      collection: 'categories',
      overrideAccess: true,
      data: { name: 'Engineering', slug: 'engineering' },
    })

    await payload.update({
      collection: 'posts',
      id: draft.id,
      overrideAccess: true,
      data: { categories: [category.id] },
    })

    const anon = await payload.find({
      collection: 'posts',
      overrideAccess: false,
      depth: 2,
      where: { 'categories.slug': { equals: 'engineering' } },
    })
    expect(anon.docs).toHaveLength(0)
  })
})
