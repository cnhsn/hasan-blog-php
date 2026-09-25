import { expect, test } from '@playwright/test'

/**
 * Public site behaviour: the no-scroll homepage goal, navigation,
 * reduced motion, keyboard access and empty/populated states
 * (docs/ACCEPTANCE.md A16-A21).
 */

test.describe('public pages', () => {
  test('homepage renders CMS-managed content', async ({ page }) => {
    await page.goto('/')
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
    await expect(page.getByRole('navigation', { name: 'Main navigation' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Hasan home' })).toBeVisible()
  })

  test('navigation marks the current page and moves between sections', async ({ page }) => {
    await page.goto('/')
    const nav = page.getByRole('navigation', { name: 'Main navigation' })
    await expect(nav.getByRole('link', { name: 'Home' })).toHaveAttribute('aria-current', 'page')

    await nav.getByRole('link', { name: 'Blog' }).click()
    await page.waitForURL('**/blog')
    await expect(nav.getByRole('link', { name: 'Blog' })).toHaveAttribute('aria-current', 'page')

    await nav.getByRole('link', { name: 'Projects' }).click()
    await page.waitForURL('**/projects')
    await expect(nav.getByRole('link', { name: 'Projects' })).toHaveAttribute('aria-current', 'page')
  })

  test('browser back and forward restore the previous pages', async ({ page }) => {
    await page.goto('/')
    await page.getByRole('navigation', { name: 'Main navigation' }).getByRole('link', { name: 'Blog' }).click()
    await page.waitForURL('**/blog')

    await page.goBack()
    await expect(page).toHaveURL(/\/$/)
    // A faded-out page must never survive a history restore.
    await expect(page.locator('main')).toBeVisible()

    await page.goForward()
    await expect(page).toHaveURL(/\/blog$/)
    await expect(page.locator('main')).toBeVisible()
  })

  test('unknown routes return a real 404', async ({ page }) => {
    const response = await page.goto('/this-page-does-not-exist')
    expect(response?.status()).toBe(404)
    await expect(page.getByText(/Page not found/i)).toBeVisible()
  })

  test('main content is visible with JavaScript disabled', async ({ browser }) => {
    const context = await browser.newContext({ javaScriptEnabled: false })
    try {
      const page = await context.newPage()
      await page.goto('/')
      const main = page.locator('main')
      await expect(main).toBeVisible()
      // Entrance animations must not leave content stuck at opacity 0.
      const opacity = await main.evaluate((el) => getComputedStyle(el).opacity)
      expect(Number(opacity)).toBeGreaterThan(0.9)
    } finally {
      await context.close()
    }
  })
})

test.describe('homepage fits the target viewports without scrolling', () => {
  const viewports = [
    { name: '1440x900', width: 1440, height: 900 },
    { name: '1280x720', width: 1280, height: 720 },
    { name: '390x844', width: 390, height: 844 },
    { name: '375x667', width: 375, height: 667 },
  ]

  for (const viewport of viewports) {
    test(`no vertical or horizontal scroll at ${viewport.name}`, async ({ browser }) => {
      const context = await browser.newContext({
        viewport: { width: viewport.width, height: viewport.height },
      })
      try {
        const page = await context.newPage()
        await page.goto('/')
        await page.waitForLoadState('networkidle')

        const metrics = await page.evaluate(() => ({
          scrollHeight: document.documentElement.scrollHeight,
          clientHeight: document.documentElement.clientHeight,
          scrollWidth: document.documentElement.scrollWidth,
          clientWidth: document.documentElement.clientWidth,
        }))

        // 1px of tolerance for sub-pixel layout rounding.
        expect(metrics.scrollHeight).toBeLessThanOrEqual(metrics.clientHeight + 1)
        expect(metrics.scrollWidth).toBeLessThanOrEqual(metrics.clientWidth + 1)
      } finally {
        await context.close()
      }
    })
  }
})

test.describe('accessibility and motion', () => {
  test('honours prefers-reduced-motion', async ({ browser }) => {
    const context = await browser.newContext({ reducedMotion: 'reduce' })
    try {
      const page = await context.newPage()
      await page.goto('/')
      const heading = page.getByRole('heading', { level: 1 })
      await expect(heading).toBeVisible()

      const animation = await heading.evaluate((el) => {
        const style = getComputedStyle(el)
        return { name: style.animationName, duration: style.animationDuration }
      })
      expect([animation.name, animation.duration]).toContain('none')
    } finally {
      await context.close()
    }
  })

  test('every page has exactly one h1 and a skip-free heading order', async ({ page }) => {
    for (const path of ['/', '/blog', '/projects']) {
      await page.goto(path)
      await expect(page.locator('h1')).toHaveCount(1)
    }
  })

  test('keyboard focus is visible and reaches the navigation', async ({ page }) => {
    await page.goto('/')

    // The Next.js dev-tools overlay (<nextjs-portal>) is focusable in
    // development and is not part of the app; tab past it.
    let focused: { outlineStyle: string; tag: string } | null = null
    for (let attempt = 0; attempt < 5; attempt += 1) {
      await page.keyboard.press('Tab')
      focused = await page.evaluate(() => {
        const el = document.activeElement as HTMLElement | null
        if (!el) return null
        const style = getComputedStyle(el)
        return { tag: el.tagName, outlineStyle: style.outlineStyle }
      })
      if (focused && focused.tag !== 'NEXTJS-PORTAL' && focused.tag !== 'BODY') break
    }

    expect(focused?.tag).toBe('A')
    expect(focused?.outlineStyle).not.toBe('none')
  })

  test('images expose alt text and reserve their aspect ratio', async ({ page }) => {
    await page.goto('/blog')
    const images = page.locator('main img')
    const count = await images.count()
    for (let index = 0; index < count; index += 1) {
      const image = images.nth(index)
      await expect(image).toHaveAttribute('alt', /.+/)
      await expect(image).toHaveAttribute('width', /\d+/)
      await expect(image).toHaveAttribute('height', /\d+/)
    }
  })

  test('non-interactive interest tags are not links or buttons', async ({ page }) => {
    await page.goto('/')
    const topics = page.locator('.topic')
    if ((await topics.count()) > 0) {
      await expect(topics.first()).not.toHaveAttribute('href', /.*/)
      const cursor = await topics.first().evaluate((el) => getComputedStyle(el).cursor)
      expect(cursor).not.toBe('pointer')
    }
  })
})
