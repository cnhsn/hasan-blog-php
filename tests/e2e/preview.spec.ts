import { expect, test } from '@playwright/test'

import { createPreviewToken } from '../../src/lib/preview'
import { authenticateBrowser, createPost, deletePost, lexical, loginAsOwner } from './helpers'

const DRAFT_SLUG = 'e2e-preview-draft'
const DRAFT_BODY = 'This draft body must never reach an anonymous visitor.'

/**
 * Draft preview isolation (docs/ACCEPTANCE.md A08): the owner sees the
 * draft, a concurrent anonymous visitor does not, and exiting preview
 * clears the state.
 */
test.describe('draft preview isolation', () => {
  let token: string
  let postId: number | string

  test.beforeEach(async ({ request }) => {
    token = await loginAsOwner(request)
    const post = await createPost(request, token, {
      title: 'E2E preview draft',
      slug: DRAFT_SLUG,
      excerpt: 'Draft excerpt for the preview isolation test.',
      content: lexical(DRAFT_BODY),
      authorName: 'Hasan',
      _status: 'draft',
    })
    postId = post.id
  })

  test.afterEach(async ({ request }) => {
    if (postId) await deletePost(request, token, postId)
  })

  test('owner previews a draft that a concurrent anonymous visitor cannot see', async ({
    browser,
    baseURL,
  }) => {
    const ownerContext = await browser.newContext()
    const visitorContext = await browser.newContext()

    try {
      await authenticateBrowser(ownerContext, token, baseURL!)

      const ownerPage = await ownerContext.newPage()
      const previewToken = createPreviewToken({ collection: 'posts', slug: DRAFT_SLUG })
      await ownerPage.goto(
        `/preview?collection=posts&slug=${DRAFT_SLUG}&token=${encodeURIComponent(previewToken)}`,
      )

      await expect(ownerPage).toHaveURL(new RegExp(`/blog/${DRAFT_SLUG}$`))
      await expect(ownerPage.getByRole('heading', { name: 'E2E preview draft' })).toBeVisible()
      await expect(ownerPage.getByText(DRAFT_BODY)).toBeVisible()
      await expect(ownerPage.getByText(/Draft preview/i)).toBeVisible()

      // Same moment, separate browser context: the draft must not exist.
      const visitorPage = await visitorContext.newPage()
      const visitorResponse = await visitorPage.goto(`/blog/${DRAFT_SLUG}`)
      expect(visitorResponse?.status()).toBe(404)
      await expect(visitorPage.getByText(DRAFT_BODY)).toHaveCount(0)

      // The draft must also be absent from the anonymous listing and feeds.
      await visitorPage.goto('/blog')
      await expect(visitorPage.getByText('E2E preview draft')).toHaveCount(0)

      const rss = await visitorPage.request.get('/rss.xml')
      expect(await rss.text()).not.toContain('E2E preview draft')

      const sitemap = await visitorPage.request.get('/sitemap.xml')
      expect(await sitemap.text()).not.toContain(DRAFT_SLUG)

      // Exiting preview clears draft state for the owner too.
      await ownerPage.getByRole('link', { name: /Exit preview/i }).click()
      await ownerPage.waitForURL(new RegExp(`/blog/${DRAFT_SLUG}$`))
      await expect(ownerPage.getByText(/Draft preview/i)).toHaveCount(0)

      const afterExit = await ownerPage.goto(`/blog/${DRAFT_SLUG}`)
      expect(afterExit?.status()).toBe(404)
    } finally {
      await ownerContext.close()
      await visitorContext.close()
    }
  })

  test('preview entry is refused without an owner session', async ({ page }) => {
    const previewToken = createPreviewToken({ collection: 'posts', slug: DRAFT_SLUG })
    const response = await page.goto(
      `/preview?collection=posts&slug=${DRAFT_SLUG}&token=${encodeURIComponent(previewToken)}`,
    )
    expect(response?.status()).toBe(401)
  })

  test('preview entry is refused with a valid session but an invalid token', async ({
    browser,
    baseURL,
  }) => {
    const ownerContext = await browser.newContext()
    try {
      await authenticateBrowser(ownerContext, token, baseURL!)
      const ownerPage = await ownerContext.newPage()
      const response = await ownerPage.goto(
        `/preview?collection=posts&slug=${DRAFT_SLUG}&token=tampered.token`,
      )
      expect(response?.status()).toBe(401)
    } finally {
      await ownerContext.close()
    }
  })
})
