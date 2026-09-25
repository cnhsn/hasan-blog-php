/**
 * Explicit, local/server-only owner bootstrap CLI (docs/SECURITY.md:
 * "Bootstrap the first owner only via an explicit local/server CLI with
 * secret input; fail if an owner already exists."). Uses the Local API
 * with `overrideAccess: true` and `context: { isBootstrapCli: true }` —
 * the only caller allowed past src/hooks/restrictUserCreation.ts.
 */
import { getPayload } from 'payload'

import config from '../src/payload.config.ts'
import { closePrompts, promptHidden, promptText } from './lib/prompt.mjs'

async function main() {
  const payload = await getPayload({ config })

  const existing = await payload.count({ collection: 'users', overrideAccess: true })
  if (existing.totalDocs > 0) {
    console.error('An owner account already exists. Refusing to bootstrap a second one.')
    process.exitCode = 1
    return
  }

  const email = await promptText('Owner email: ')
  if (!email || !email.includes('@')) {
    console.error('A valid email is required.')
    process.exitCode = 1
    return
  }

  const password = await promptHidden('Owner password (min 8 characters): ')
  if (password.length < 8) {
    console.error('Password must be at least 8 characters.')
    process.exitCode = 1
    return
  }

  const confirmPassword = await promptHidden('Confirm password: ')
  if (password !== confirmPassword) {
    console.error('Passwords do not match.')
    process.exitCode = 1
    return
  }

  const displayNameInput = await promptText('Display name [Hasan]: ')
  const displayName = displayNameInput || 'Hasan'

  await payload.create({
    collection: 'users',
    context: { isBootstrapCli: true },
    data: {
      email,
      password,
      displayName,
      role: 'owner',
    },
    overrideAccess: true,
  })

  console.log(`Owner account created for ${email}. You can now log in at /admin.`)
}

main()
  .then(() => {
    closePrompts()
    process.exit(process.exitCode ?? 0)
  })
  .catch((error) => {
    closePrompts()
    console.error('Bootstrap failed:', error instanceof Error ? error.message : error)
    process.exit(1)
  })
