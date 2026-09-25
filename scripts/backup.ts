/**
 * Consistent database dump plus a media inventory and object copies
 * (docs/OPERATIONS.md "Backup and restore"). A database-only dump is
 * not a complete backup, so media objects are copied alongside it and
 * checksummed.
 *
 * Writes to backups/<timestamp>/ which is git-ignored. Nothing here
 * encrypts the output: encryption and off-host transfer are the
 * operator's step, documented in the README runbook, because the key
 * must not live next to the data.
 */
import { spawnSync } from 'node:child_process'
import { createHash } from 'node:crypto'
import { mkdirSync, writeFileSync } from 'node:fs'
import path from 'node:path'

import { getPayload } from 'payload'

import { getEnv } from '../src/lib/env.ts'
import config from '../src/payload.config.ts'

function timestamp(): string {
  return new Date().toISOString().replace(/[:.]/g, '-')
}

function parseDatabaseUri(uri: string) {
  const url = new URL(uri)
  return {
    host: url.hostname,
    port: url.port || '5432',
    user: decodeURIComponent(url.username),
    password: decodeURIComponent(url.password),
    database: url.pathname.replace(/^\//, ''),
  }
}

/**
 * Runs pg_dump inside the Postgres container so the client version
 * always matches the server and no local client install is required.
 */
function dumpDatabase(targetFile: string, db: ReturnType<typeof parseDatabaseUri>): void {
  const result = spawnSync(
    'docker',
    [
      'compose',
      'exec',
      '-T',
      '-e',
      `PGPASSWORD=${db.password}`,
      'postgres',
      'pg_dump',
      '-U',
      db.user,
      '-d',
      db.database,
      '--format=custom',
      '--no-owner',
    ],
    { maxBuffer: 1024 * 1024 * 1024, encoding: 'buffer' },
  )

  if (result.status !== 0) {
    throw new Error(`pg_dump failed: ${result.stderr?.toString() ?? 'unknown error'}`)
  }
  writeFileSync(targetFile, result.stdout)
}

async function main() {
  const env = getEnv()
  const db = parseDatabaseUri(env.databaseUri)

  const stamp = timestamp()
  const outDir = path.resolve(process.cwd(), 'backups', stamp)
  const mediaDir = path.join(outDir, 'media')
  mkdirSync(mediaDir, { recursive: true })

  console.log(`Backing up database "${db.database}" and media bucket "${env.s3.bucket}"...`)

  const dumpFile = path.join(outDir, 'database.dump')
  dumpDatabase(dumpFile, db)
  console.log(`  database -> ${path.relative(process.cwd(), dumpFile)}`)

  // Media inventory + object copies, read through Payload so the same
  // storage adapter (and therefore the same credentials) is used.
  const payload = await getPayload({ config })
  const media = await payload.find({ collection: 'media', overrideAccess: true, limit: 10_000 })

  const inventory: Array<Record<string, unknown>> = []
  let copied = 0

  for (const doc of media.docs) {
    const filenames = [
      doc.filename,
      ...Object.values(doc.sizes ?? {}).map((size) => (size as { filename?: null | string })?.filename),
    ].filter((name): name is string => Boolean(name))

    for (const filename of filenames) {
      const response = await fetch(`${env.publicSiteUrl}/api/media/file/${encodeURIComponent(filename)}`)
      if (!response.ok) {
        console.warn(`  ! could not read ${filename} (HTTP ${response.status}) — skipped`)
        continue
      }
      const bytes = Buffer.from(await response.arrayBuffer())
      writeFileSync(path.join(mediaDir, filename), bytes)
      inventory.push({
        id: doc.id,
        filename,
        bytes: bytes.byteLength,
        sha256: createHash('sha256').update(bytes).digest('hex'),
        visibility: doc.visibility,
      })
      copied += 1
    }
  }

  writeFileSync(
    path.join(outDir, 'media-inventory.json'),
    `${JSON.stringify({ createdAt: new Date().toISOString(), bucket: env.s3.bucket, objects: inventory }, null, 2)}\n`,
  )

  // Configuration inventory: names only, never values.
  writeFileSync(
    path.join(outDir, 'configuration-inventory.json'),
    `${JSON.stringify(
      {
        createdAt: new Date().toISOString(),
        note: 'Variable names only. Values are secrets and are deliberately not recorded.',
        requiredEnvironmentVariables: [
          'NODE_ENV',
          'PUBLIC_SITE_URL',
          'PORT',
          'PAYLOAD_SECRET',
          'DATABASE_URI',
          'S3_ENDPOINT',
          'S3_REGION',
          'S3_BUCKET',
          'S3_ACCESS_KEY_ID',
          'S3_SECRET_ACCESS_KEY',
          'S3_FORCE_PATH_STYLE',
          'SMTP_HOST',
          'SMTP_PORT',
          'SMTP_SECURE',
          'SMTP_USER',
          'SMTP_PASS',
          'SMTP_FROM_ADDRESS',
          'SMTP_FROM_NAME',
        ],
        databaseName: db.database,
      },
      null,
      2,
    )}\n`,
  )

  const counts = await Promise.all(
    (['posts', 'projects', 'media', 'categories', 'tags', 'redirects', 'users'] as const).map(
      async (collection) => [
        collection,
        (await payload.count({ collection, overrideAccess: true })).totalDocs,
      ],
    ),
  )

  writeFileSync(
    path.join(outDir, 'manifest.json'),
    `${JSON.stringify(
      {
        createdAt: new Date().toISOString(),
        database: db.database,
        bucket: env.s3.bucket,
        documentCounts: Object.fromEntries(counts),
        mediaObjects: copied,
      },
      null,
      2,
    )}\n`,
  )

  console.log(`  media    -> ${copied} object(s)`)
  console.log(`Backup complete: ${path.relative(process.cwd(), outDir)}`)
  console.log('Encrypt this directory and copy it off-host — see the README runbook.')
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error('Backup failed:', error instanceof Error ? error.message : error)
    process.exit(1)
  })
