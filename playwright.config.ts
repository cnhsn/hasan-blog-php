import { defineConfig, devices } from '@playwright/test'

const BASE_URL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost:3000'

/**
 * Browser acceptance tests run against a real running app backed by the
 * real local services. They drive the development database, so every
 * spec creates and removes its own fixtures through the authenticated
 * REST API (see tests/e2e/helpers.ts).
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false,
  workers: 1,
  timeout: 90_000,
  expect: { timeout: 15_000 },
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],
  use: {
    baseURL: BASE_URL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium-desktop',
      use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } },
    },
    {
      name: 'chromium-mobile',
      use: { ...devices['Pixel 5'] },
    },
  ],
  webServer: {
    command: 'node node_modules/next/dist/bin/next dev',
    url: BASE_URL,
    reuseExistingServer: true,
    timeout: 180_000,
  },
})
