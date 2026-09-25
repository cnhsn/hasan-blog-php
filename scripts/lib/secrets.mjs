import { randomBytes } from 'node:crypto'

/** Alphanumeric-only random string (safe to embed in a connection URI without escaping). */
export function randomAlphanumeric(length) {
  let out = ''
  while (out.length < length) {
    out += randomBytes(length).toString('base64').replace(/[^A-Za-z0-9]/g, '')
  }
  return out.slice(0, length)
}

export function randomHex(bytes) {
  return randomBytes(bytes).toString('hex')
}
