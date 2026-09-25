import { expect, test } from '@playwright/test'

import { createPost, deletePost, lexical, loginAsOwner } from './helpers'

const SLUG = 'e2e-version-restore'

/**
 * Version restore through the REST endpoint the admin UI calls
 * (docs/ACCEPTANCE.md A28): restoring an older revision as a draft must
 * leave the published page exactly as visitors saw it.
 */
test.describe('version restore', () => {
  let token: string
  let postId: number | string

  test.beforeEach(async ({ request }) => {
    token = await loginAsOwner(request)
    const post = await createPost(request, token, {
      title: 'Revision one',
      slug: SLUG,
      excerpt: 'First excerpt.',
      content: lexical('First body.'),
      authorName: 'Hasan',
      _status: 'published',
    })
    postId = post.id
  })

  test.afterEach(async ({ request }) => {
    if (postId) await deletePost(request, token, postId)
  })

  test('restoring an older revision as a draft leaves the public page unchanged', async ({
    page,
    request,
  }) => {
    const auth = { Authorization: `JWT ${token}`, 'Content-Type': 'application/json' }

    // Publish a second revision and confirm visitors see it.
    const update = await request.patch(`/api/posts/${postId}`, {
      headers: auth,
      data: { title: 'Revision two', content: lexical('Second body.'), _status: 'published' },
    })
    expect(update.ok()).toBe(true)

    await page.goto(`/blog/${SLUG}`)
    await expect(page.getByRole('heading', { name: 'Revision two' })).toBeVisible()
    await expect(page.getByText('Second body.')).toBeVisible()

    // Find the stored revision that still holds the original title.
    const versionsResponse = await request.get(
      `/api/posts/versions?where[parent][equals]=${postId}&limit=50&depth=0`,
      { headers: auth },
    )
    expect(versionsResponse.ok()).toBe(true)
    const versions = (await versionsResponse.json()) as {
      docs: Array<{ id: number | string; version: { title: string } }>
    }
    const original = versions.docs.find((doc) => doc.version.title === 'Revision one')
    expect(original, 'expected a stored revision titled "Revision one"').toBeTruthy()

    // Restore it as a draft — the flag the admin UI sends.
    const restore = await request.post(`/api/posts/versions/${original!.id}?draft=true`, {
      headers: auth,
    })
    expect(restore.ok()).toBe(true)

    // Visitors must still see the published revision.
    await page.goto(`/blog/${SLUG}`)
    await expect(page.getByRole('heading', { name: 'Revision two' })).toBeVisible()
    await expect(page.getByText('First body.')).toHaveCount(0)

    // The owner's draft holds the restored revision.
    const draftRead = await request.get(`/api/posts/${postId}?draft=true&depth=0`, { headers: auth })
    const draftDoc = (await draftRead.json()) as { _status: string; title: string }
    expect(draftDoc.title).toBe('Revision one')
    expect(draftDoc._status).toBe('draft')
  })
})
