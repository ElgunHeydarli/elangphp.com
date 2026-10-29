import { NextRequest } from 'next/server';
import { backendOrigin } from '../../../lib/backend';
export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';
const MAX_BODY_BYTES = 32_768;
const routes: [RegExp, readonly string[]][] = [
  [/^session$/, ['GET']],
  [/^session\/(login|register|mfa|logout)$/, ['POST']],
  [/^notes$/, ['GET', 'POST']],
  [/^notes\/[1-9][0-9]*$/, ['GET', 'PUT', 'DELETE']],
];
function json(message: string, status: number) { return Response.json({ message }, { status, headers: { 'Cache-Control': 'private, no-store' } }); }
async function bodyText(request: NextRequest): Promise<string | null> {
  if (!request.body) return '';
  const reader = request.body.getReader(); const chunks: Uint8Array[] = []; let size = 0;
  try {
    while (true) { const chunk = await reader.read(); if (chunk.done) break; size += chunk.value.byteLength; if (size > MAX_BODY_BYTES) { await reader.cancel(); return null; } chunks.push(chunk.value); }
  } finally { reader.releaseLock(); }
  const bytes = new Uint8Array(size); let offset = 0; for (const chunk of chunks) { bytes.set(chunk, offset); offset += chunk.byteLength; }
  return new TextDecoder().decode(bytes);
}
async function proxy(request: NextRequest, context: { params: Promise<{ path: string[] }> }) {
  const { path } = await context.params; const route = path.join('/');
  const allowed = routes.find(([pattern]) => pattern.test(route));
  if (!allowed) return json('API route not found.', 404);
  if (!allowed[1].includes(request.method)) return json('Method not allowed.', 405);
  const unsafe = request.method !== 'GET';
  const origin = request.headers.get('origin');
  // Next may normalize its internal URL to localhost; Host retains the browser-facing authority.
  let requestOrigin: string;
  try { requestOrigin = new URL(`${request.nextUrl.protocol}//${request.headers.get('host') || request.nextUrl.host}`).origin; }
  catch { return json('Invalid request host.', 400); }
  if (unsafe && origin && origin !== requestOrigin) return json('Cross-origin requests are not allowed.', 403);
  let body: string | undefined;
  if (unsafe) {
    if (!request.headers.get('content-type')?.toLowerCase().includes('application/json')) return json('Use application/json.', 415);
    const text = await bodyText(request); if (text === null) return json('Request body is too large.', 413); body = text;
  }
  const target = new URL('/api/' + route, backendOrigin());
  if (request.method === 'GET' && route === 'notes') {
    const page = request.nextUrl.searchParams.get('page');
    if (page !== null) { if (!/^[1-9][0-9]{0,6}$/.test(page)) return json('Choose a valid page.', 400); target.searchParams.set('page', page); }
  }
  const headers = new Headers({ Accept: 'application/json' });
  for (const name of ['cookie', 'x-csrf-token', 'content-type']) { const value = request.headers.get(name); if (value) headers.set(name, value); }
  // Origin is checked locally. The server-to-server hop does not use browser CORS.
  try {
    const upstream = await fetch(target, { method: request.method, headers, body, cache: 'no-store', redirect: 'manual', signal: AbortSignal.timeout(15_000) });
    if (upstream.status >= 300 && upstream.status < 400) { await upstream.body?.cancel(); return json('The backend returned an unexpected redirect.', 502); }
    const responseHeaders = new Headers({ 'Cache-Control': 'private, no-store', Vary: 'Cookie', 'X-Content-Type-Options': 'nosniff' });
    for (const name of ['content-type', 'retry-after', 'x-request-id']) { const value = upstream.headers.get(name); if (value) responseHeaders.set(name, value); }
    // Forward every cookie separately. Remove only Domain so it belongs to the frontend host;
    // retain HttpOnly, Secure, SameSite, Path, expiry, and deletion attributes.
    for (const cookie of upstream.headers.getSetCookie()) responseHeaders.append('Set-Cookie', cookie.replace(/;\s*Domain=[^;]+/gi, ''));
    return new Response(upstream.body, { status: upstream.status, headers: responseHeaders });
  } catch { return json('The Elang backend is unavailable. Please try again.', 502); }
}
export const GET = proxy;
export const POST = proxy;
export const PUT = proxy;
export const DELETE = proxy;
