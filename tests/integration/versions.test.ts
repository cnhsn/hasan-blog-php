import type { Payload } from 'payload'
import { beforeAll, beforeEach, describe, expect, it } from 'vitest'

import { getTestPayload, lexicalParagraph, resetContent } from '../helpers/payload'

/**
 * Version history and restore (docs/ACCEPTANCE.md A28): restoring an
 * older revision must produce an editable draft and must not silently
 * replace what visitors currently see.
 */
describe('version history', () => {
  let payload: Payload

  beforeAll(async () => {
    payload = await getTestPayload()
  })

  beforeEach(async () => {
    await resetContent()
  })

  async function createPublishedPost() {
    return payload.create({
      collection: 'posts',
      overrideAccess: true,
      data: {
        title: 'Version one',
        slug: 'versioned-post',
        excerpt: 'First excerpt.',
        content: lexicalParagraph('First body.'),
        authorName: 'Hasan',
        _status: 'published',
      },
    })
  }

  it('keeps a version history for each change', async () => {
    const post = await createPublishedPost()

    await payload.update({
      collection: 'posts',
      id: post.id,
      overrideAccess: true,
      data: { title: 'Version two', _status: 'published' },
    })

    const versions = await payload.findVersions({
      collection: 'posts',
      overrideAccess: true,
      where: { parent: { equals: post.id } },
      sort: '-updatedAt',
    })

    expect(versions.totalDocs).toBeGreaterThanOrEqual(2)
    expect(versions.docs.map((v) => v.version.title)).toContain('Version one')
  })

  it('denies anonymous access to version history', async () => {
    const post = await createPublishedPost()
    await expect(
      payload.findVersions({
        collection: 'posts',
        overrideAccess: false,
        where: { parent: { equals: post.id } },
      }),
    ).rejects.toThrow()
  })

  /**
   * Restoring into a draft is covered in tests/e2e/versions.spec.ts,
   * against the REST endpoint the admin UI actually calls.
   * `payload.restoreVersion({ draft: true })` cannot be used here: the
   * Local API wrapper never forwards `draft` to the operation (see
   * docs/DECISIONS.md), so it would republish immediately.
   */
})
