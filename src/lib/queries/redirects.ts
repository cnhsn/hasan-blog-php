import { getPayloadClient } from '@/lib/payload'

export async function resolveRedirect(pathname: string): Promise<string | null> {
  const payload = await getPayloadClient()

  const result = await payload.find({
    collection: 'redirects',
    overrideAccess: false,
    where: { sourcePath: { equals: pathname } },
    limit: 1,
  })

  return result.docs[0]?.targetPath ?? null
}
