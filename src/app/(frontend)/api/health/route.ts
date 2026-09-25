import { getPayloadClient } from '@/lib/payload'

export const dynamic = 'force-dynamic'

export async function GET() {
  try {
    const payload = await getPayloadClient()
    // Cheap real query, not just "did the process start" — proves the
    // database connection actually works. No internal details in the response.
    await payload.count({ collection: 'redirects', overrideAccess: true })
    return Response.json({ status: 'ok' })
  } catch {
    return Response.json({ status: 'error' }, { status: 503 })
  }
}
