import { fileURLToPath } from 'node:url'

import { defineConfig } from 'vitest/config'

/**
 * Integration tests run against the real local Postgres and the real
 * local Garage (S3) service — mocked success responses do not satisfy
 * the persistence/security acceptance cases (docs/ACCEPTANCE.md). They
 * use a dedicated disposable database and bucket so a test run can
 * never touch development data.
 */
const TEST_DATABASE = 'hasan_journal_test'
const TEST_BUCKET = 'hasan-journal-test'

function testDatabaseUri(): string {
  const uri = process.env.DATABASE_URI
  if (!uri) return ''
  return uri.replace(/\/[^/?]+(\?|$)/, `/${TEST_DATABASE}$1`)
}

export default defineConfig({
  resolve: {
    alias: {
      '@payload-config': fileURLToPath(new URL('./src/payload.config.ts', import.meta.url)),
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  test: {
    environment: 'node',
    include: ['tests/**/*.test.ts'],
    testTimeout: 60_000,
    hookTimeout: 180_000,
    // One shared test database: keep files from racing each other.
    fileParallelism: false,
    globalSetup: ['tests/globalSetup.ts'],
    env: {
      NODE_ENV: 'test',
      DATABASE_URI: testDatabaseUri(),
      S3_BUCKET: TEST_BUCKET,
    },
  },
})
