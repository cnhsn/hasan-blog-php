import path from 'node:path'
import { fileURLToPath } from 'node:url'

import { postgresAdapter } from '@payloadcms/db-postgres'
import { nodemailerAdapter } from '@payloadcms/email-nodemailer'
import { s3Storage } from '@payloadcms/storage-s3'
import { buildConfig } from 'payload'
import sharp from 'sharp'

import { Categories } from '@/collections/Categories'
import { Media } from '@/collections/Media'
import { Posts } from '@/collections/Posts'
import { Projects } from '@/collections/Projects'
import { Redirects } from '@/collections/Redirects'
import { Tags } from '@/collections/Tags'
import { Users } from '@/collections/Users'
import { SiteSettings } from '@/globals/SiteSettings'
import { createRichTextEditor } from '@/lib/editor'
import { getEnv } from '@/lib/env'

const dirname = path.dirname(fileURLToPath(import.meta.url))
const env = getEnv()

export default buildConfig({
  admin: {
    user: Users.slug,
  },
  editor: createRichTextEditor(),
  collections: [Users, Media, Categories, Tags, Posts, Projects, Redirects],
  globals: [SiteSettings],
  secret: env.payloadSecret,
  serverURL: env.publicSiteUrl,
  cors: [env.publicSiteUrl],
  csrf: [env.publicSiteUrl],
  // Unused by this app (frontend reads via the Local API, admin UI uses
  // REST) — disabled per docs/ARCHITECTURE.md ("If GraphQL is unused,
  // disable it using a supported mechanism and test that it is
  // unavailable").
  graphQL: {
    disable: true,
  },
  typescript: {
    outputFile: path.resolve(dirname, 'payload-types.ts'),
  },
  db: postgresAdapter({
    pool: {
      connectionString: env.databaseUri,
    },
    migrationDir: path.resolve(dirname, 'migrations'),
    // Explicit migrations only in production; dev may fall back to Payload's
    // schema push when no migrations have been created yet
    // (docs/ARCHITECTURE.md: "disable production development schema-push behavior").
    push: env.isProduction ? false : undefined,
  }),
  sharp,
  bodyParser: {
    // 10 MiB per file, docs/CONTENT_MODEL.md Media limit.
    limits: {
      fileSize: 10 * 1024 * 1024,
    },
  },
  email: nodemailerAdapter({
    defaultFromAddress: env.smtp.fromAddress,
    defaultFromName: env.smtp.fromName,
    transportOptions: {
      host: env.smtp.host,
      port: env.smtp.port,
      secure: env.smtp.secure,
      auth: env.smtp.user ? { user: env.smtp.user, pass: env.smtp.pass } : undefined,
    },
  }),
  plugins: [
    s3Storage({
      bucket: env.s3.bucket,
      collections: {
        media: true,
      },
      config: {
        credentials: {
          accessKeyId: env.s3.accessKeyId,
          secretAccessKey: env.s3.secretAccessKey,
        },
        endpoint: env.s3.endpoint,
        forcePathStyle: env.s3.forcePathStyle,
        region: env.s3.region,
      },
    }),
  ],
})
