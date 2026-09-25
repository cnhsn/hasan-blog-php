import config from '@payload-config'
import { getPayload, type Payload } from 'payload'

let cached: Promise<Payload> | undefined

/** Memoized Payload Local API client for use in server components/route handlers. */
export function getPayloadClient(): Promise<Payload> {
  if (!cached) {
    cached = getPayload({ config })
  }
  return cached
}
