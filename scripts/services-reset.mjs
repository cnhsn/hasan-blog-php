#!/usr/bin/env node
/**
 * DESTRUCTIVE: stops local services and deletes their Docker volumes
 * (Postgres data, Garage media, Mailpit inbox). Requires typing an exact
 * confirmation phrase — never run this against anything but disposable
 * local dev data (docs/OPERATIONS.md: "Any reset command must be
 * explicit and require confirmation of target").
 */
import { spawnSync } from 'node:child_process'
import readline from 'node:readline/promises'

const CONFIRM_PHRASE = 'delete local dev data'

const rl = readline.createInterface({ input: process.stdin, output: process.stdout })
console.log('This will permanently delete the local Postgres, Garage and Mailpit volumes.')
console.log('This cannot target a production deployment — it only ever runs against compose.yaml locally.')
const answer = await rl.question(`Type "${CONFIRM_PHRASE}" to confirm: `)
rl.close()

if (answer.trim() !== CONFIRM_PHRASE) {
  console.log('Not confirmed. Nothing was deleted.')
  process.exit(1)
}

const result = spawnSync('docker', ['compose', '-f', 'compose.yaml', 'down', '-v'], {
  stdio: 'inherit',
})
process.exit(result.status ?? 1)
