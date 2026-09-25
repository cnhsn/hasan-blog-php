import { getPayloadClient } from '@/lib/payload'
import type { Category } from '@/payload-types'

export async function listCategories(): Promise<Category[]> {
  const payload = await getPayloadClient()
  const result = await payload.find({
    collection: 'categories',
    overrideAccess: false,
    limit: 100,
    sort: 'name',
  })
  return result.docs
}
