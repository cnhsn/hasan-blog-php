import { execFileSync } from 'node:child_process'

const TEST_BUCKET = 'hasan-journal-test'

function garage(args: string[]): string {
  return execFileSync('docker', ['compose', 'exec', '-T', 'garage', '/garage', ...args], {
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  })
}

/**
 * Provisions the disposable test bucket in the local Garage service.
 * The test database itself is created automatically by Payload's
 * Postgres adapter on first connect.
 */
export default function globalSetup() {
  const accessKeyId = process.env.S3_ACCESS_KEY_ID
  if (!accessKeyId) {
    throw new Error('S3_ACCESS_KEY_ID is not set — run `pnpm setup:local` and `pnpm services:up` first.')
  }

  try {
    const buckets = garage(['bucket', 'list'])
    if (!buckets.includes(TEST_BUCKET)) {
      garage(['bucket', 'create', TEST_BUCKET])
    }
    garage(['bucket', 'allow', '--read', '--write', TEST_BUCKET, '--key', accessKeyId])
  } catch (error) {
    const detail = error instanceof Error ? error.message : String(error)
    throw new Error(
      `Could not provision the '${TEST_BUCKET}' bucket in the local Garage service. ` +
        `Are the local services running (pnpm services:up)?\n${detail}`,
    )
  }
}
