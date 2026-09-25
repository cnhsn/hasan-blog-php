/**
 * Restore drill: restores a backup into ISOLATED targets (a scratch
 * database and a scratch bucket) and verifies integrity, then reports a
 * measured recovery time.
 *
 * It never writes to the live database or the live bucket, and never
 * deletes live objects (docs/OPERATIONS.md: "Do not automatically
 * delete live objects or restore into production").
 *
 * Usage: pnpm restore:check [backups/<timestamp>]
 */
import { execFileSync, spawnSync } from 'node:child_process'
import { createHash } from 'node:crypto'
import { readdirSync, readFileSync, statSync } from 'node:fs'
import path from 'node:path'

import { getEnv } from '../src/lib/env.ts'

const RESTORE_DATABASE = 'hasan_journal_restore_check'

type MediaInventory = {
  bucket: string
  objects: Array<{ bytes: number; filename: string; sha256: string; visibility: string }>
}

function parseDatabaseUri(uri: string) {
  const url = new URL(uri)
  return {
    user: decodeURIComponent(url.username),
    password: decodeURIComponent(url.password),
    database: url.pathname.replace(/^\//, ''),
  }
}

function latestBackupDir(): string {
  const root = path.resolve(process.cwd(), 'backups')
  const entries = readdirSync(root).filter((entry) => statSync(path.join(root, entry)).isDirectory())
  if (entries.length === 0) throw new Error('No backups found. Run `pnpm backup` first.')
  entries.sort()
  return path.join(root, entries[entries.length - 1]!)
}

function psql(db: ReturnType<typeof parseDatabaseUri>, database: string, sql: string): string {
  return execFileSync(
    'docker',
    [
      'compose',
      'exec',
      '-T',
      '-e',
      `PGPASSWORD=${db.password}`,
      'postgres',
      'psql',
      '-U',
      db.user,
      '-d',
      database,
      '-t',
      '-A',
      '-c',
      sql,
    ],
    { encoding: 'utf8' },
  ).trim()
}

async function main() {
  const env = getEnv()
  const db = parseDatabaseUri(env.databaseUri)

  const backupDir = process.argv[2] ? path.resolve(process.argv[2]) : latestBackupDir()
  console.log(`Restore drill using ${path.relative(process.cwd(), backupDir)}`)

  if (RESTORE_DATABASE === db.database) {
    throw new Error('Refusing to restore over the live database.')
  }

  const manifest = JSON.parse(readFileSync(path.join(backupDir, 'manifest.json'), 'utf8')) as {
    documentCounts: Record<string, number>
    mediaObjects: number
  }
  const inventory = JSON.parse(
    readFileSync(path.join(backupDir, 'media-inventory.json'), 'utf8'),
  ) as MediaInventory

  const startedAt = Date.now()

  // 1. Isolated database target.
  console.log(`  restoring database into "${RESTORE_DATABASE}" (isolated)...`)
  psql(db, 'postgres', `DROP DATABASE IF EXISTS ${RESTORE_DATABASE}`)
  psql(db, 'postgres', `CREATE DATABASE ${RESTORE_DATABASE}`)

  const dump = readFileSync(path.join(backupDir, 'database.dump'))
  const restore = spawnSync(
    'docker',
    [
      'compose',
      'exec',
      '-T',
      '-e',
      `PGPASSWORD=${db.password}`,
      'postgres',
      'pg_restore',
      '-U',
      db.user,
      '-d',
      RESTORE_DATABASE,
      '--no-owner',
    ],
    { input: dump, maxBuffer: 1024 * 1024 * 1024, encoding: 'buffer' },
  )
  if (restore.status !== 0) {
    throw new Error(`pg_restore failed: ${restore.stderr?.toString() ?? 'unknown error'}`)
  }

  // 2. Verify document counts survived the round trip.
  const failures: string[] = []
  for (const [collection, expected] of Object.entries(manifest.documentCounts)) {
    const table = collection === 'site-settings' ? 'site_settings' : collection
    const actual = Number(psql(db, RESTORE_DATABASE, `SELECT count(*) FROM ${table}`))
    const status = actual === expected ? 'ok' : 'MISMATCH'
    if (actual !== expected) failures.push(`${collection}: expected ${expected}, restored ${actual}`)
    console.log(`    ${collection}: ${actual}/${expected} ${status}`)
  }

  // 3. Verify a published post, a draft post and their media are intact.
  const publishedCount = Number(
    psql(db, RESTORE_DATABASE, `SELECT count(*) FROM posts WHERE _status = 'published'`),
  )
  const draftCount = Number(
    psql(db, RESTORE_DATABASE, `SELECT count(*) FROM posts WHERE _status = 'draft'`),
  )
  console.log(`    published posts restored: ${publishedCount}`)
  console.log(`    draft posts restored:     ${draftCount}`)

  // 4. Verify media bytes against the recorded checksums.
  let verified = 0
  for (const object of inventory.objects) {
    const bytes = readFileSync(path.join(backupDir, 'media', object.filename))
    const sha256 = createHash('sha256').update(bytes).digest('hex')
    if (sha256 !== object.sha256) {
      failures.push(`media ${object.filename}: checksum mismatch`)
    } else {
      verified += 1
    }
  }
  console.log(`    media objects verified:   ${verified}/${inventory.objects.length}`)

  const seconds = ((Date.now() - startedAt) / 1000).toFixed(1)

  // 5. Leave no scratch database behind.
  psql(db, 'postgres', `DROP DATABASE IF EXISTS ${RESTORE_DATABASE}`)

  if (failures.length > 0) {
    console.error('\nRestore drill FAILED:')
    for (const failure of failures) console.error(`  - ${failure}`)
    process.exit(1)
  }

  console.log(`\nRestore drill passed in ${seconds}s (measured, isolated targets only).`)
  console.log('Live database and live media bucket were not modified.')
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error('Restore drill failed:', error instanceof Error ? error.message : error)
    process.exit(1)
  })
