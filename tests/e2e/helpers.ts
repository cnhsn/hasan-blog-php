import type { APIRequestContext } from '@playwright/test'

const OWNER_EMAIL = process.env.E2E_OWNER_EMAIL ?? 'test-owner@example.com'
const OWNER_PASSWORD = process.env.E2E_OWNER_PASSWORD ?? 'testpassword123'

/**
 * Logs in through the REST API and returns the JWT. Mutating REST calls
 * use the Authorization header rather than the cookie: Payload's CSRF
 * protection rejects cookie-authenticated writes that arrive without a
 * matching Origin, which is exactly the behaviour we want in production.
 */
export async function loginAsOwner(request: APIRequestContext): Promise<string> {
  const response = await request.post('/api/users/login', {
    data: { email: OWNER_EMAIL, password: OWNER_PASSWORD },
  })
  if (!response.ok()) {
    throw new Error(
      `Owner login failed (${response.status()}). Run \`pnpm admin:bootstrap\` with the ` +
        `credentials in tests/e2e/helpers.ts, or set E2E_OWNER_EMAIL / E2E_OWNER_PASSWORD.`,
    )
  }
  const body = (await response.json()) as { token: string }
  return body.token
}

export function lexical(text: string) {
  return {
    root: {
      type: 'root',
      direction: 'ltr',
      format: '',
      indent: 0,
      version: 1,
      children: [
        {
          type: 'paragraph',
          direction: 'ltr',
          format: '',
          indent: 0,
          version: 1,
          textFormat: 0,
          children: [
            { type: 'text', detail: 0, format: 0, mode: 'normal', style: '', text, version: 1 },
          ],
        },
      ],
    },
  }
}

export async function createPost(
  request: APIRequestContext,
  token: string,
  data: Record<string, unknown>,
): Promise<{ id: number | string; slug: string }> {
  const response = await request.post('/api/posts', {
    headers: { Authorization: `JWT ${token}`, 'Content-Type': 'application/json' },
    data,
  })
  if (!response.ok()) {
    throw new Error(`Failed to create post: ${response.status()} ${await response.text()}`)
  }
  const body = (await response.json()) as { doc: { id: number | string; slug: string } }
  return body.doc
}

export async function deletePost(
  request: APIRequestContext,
  token: string,
  id: number | string,
): Promise<void> {
  await request.delete(`/api/posts/${id}`, {
    headers: { Authorization: `JWT ${token}` },
  })
}

/** Sets the Payload auth cookie on a browser context so it acts as the owner. */
export async function authenticateBrowser(
  context: import('@playwright/test').BrowserContext,
  token: string,
  baseURL: string,
): Promise<void> {
  const url = new URL(baseURL)
  await context.addCookies([
    {
      name: 'payload-token',
      value: token,
      domain: url.hostname,
      path: '/',
      httpOnly: true,
      secure: url.protocol === 'https:',
      sameSite: 'Lax',
    },
  ])
}
