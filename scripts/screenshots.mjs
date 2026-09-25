#!/usr/bin/env node
/**
 * Captures the viewport matrix required by docs/DESIGN_AND_MOTION.md
 * into the git-ignored artifacts/ directory. Requires a running server
 * (pnpm dev or pnpm start) and Playwright browsers.
 *
 * Usage: node scripts/screenshots.mjs [baseUrl]
 */
import { mkdirSync } from 'node:fs'
import path from 'node:path'

import { chromium } from '@playwright/test'

const baseUrl = process.argv[2] ?? 'http://localhost:3000'
const outDir = path.resolve(process.cwd(), 'artifacts', 'screenshots')

const viewports = [
  { name: '1440x900', width: 1440, height: 900 },
  { name: '1280x720', width: 1280, height: 720 },
  { name: '390x844', width: 390, height: 844 },
  { name: '375x667', width: 375, height: 667 },
]

const pages = [
  { name: 'home', path: '/' },
  { name: 'blog', path: '/blog' },
  { name: 'projects', path: '/projects' },
  { name: 'post-detail', path: '/blog/sample-post-1' },
  { name: 'project-detail', path: '/projects/sample-project' },
]

mkdirSync(outDir, { recursive: true })

const browser = await chromium.launch()
let captured = 0

for (const viewport of viewports) {
  for (const target of pages) {
    const context = await browser.newContext({
      viewport: { width: viewport.width, height: viewport.height },
    })
    const page = await context.newPage()
    const response = await page.goto(`${baseUrl}${target.path}`, { waitUntil: 'networkidle' })
    const status = response?.status() ?? 0
    const file = path.join(outDir, `${target.name}-${viewport.name}.png`)
    await page.screenshot({ path: file, fullPage: target.name !== 'home' })
    console.log(`  ${status}  ${target.path} @ ${viewport.name} -> ${path.relative(process.cwd(), file)}`)
    captured += 1
    await context.close()
  }
}

// Reduced-motion variant of the homepage.
const reducedContext = await browser.newContext({
  viewport: { width: 1440, height: 900 },
  reducedMotion: 'reduce',
})
const reducedPage = await reducedContext.newPage()
await reducedPage.goto(baseUrl, { waitUntil: 'networkidle' })
await reducedPage.screenshot({ path: path.join(outDir, 'home-1440x900-reduced-motion.png') })
captured += 1
await reducedContext.close()

await browser.close()
console.log(`\nCaptured ${captured} screenshots into ${path.relative(process.cwd(), outDir)}`)
