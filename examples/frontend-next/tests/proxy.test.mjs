import test from 'node:test';
import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { setTimeout as delay } from 'node:timers/promises';

async function listen(server) { server.listen(0, '127.0.0.1'); await once(server, 'listening'); return server.address().port; }
async function close(server) { server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); }

test('the built Next proxy relays cookies and enforces the API boundary', { timeout: 30_000 }, async t => {
  const received = [];
  const backend = createServer(async (request, response) => {
    let body = ''; for await (const chunk of request) body += chunk;
    received.push({ path: request.url, method: request.method, headers: request.headers, body });
    response.setHeader('Content-Type', 'application/json');
    if (request.url === '/api/session') {
      response.setHeader('Set-Cookie', ['elang_session=opaque; Path=/; Domain=backend.internal; HttpOnly; SameSite=Lax', 'rotation=second; Path=/; HttpOnly; Secure; SameSite=Strict']);
      response.end(JSON.stringify({ user: null, csrfToken: 'csrf', registration: true, mailAvailable: true }));
    } else if (request.url === '/api/session/login') {
      response.statusCode = 202; response.setHeader('Set-Cookie', 'elang_session=rotated; Path=/; HttpOnly; SameSite=Lax'); response.end(JSON.stringify({ mfaRequired: true, csrfToken: 'rotated-csrf' }));
    } else if (request.url === '/api/notes' && request.method === 'POST') {
      response.statusCode = 302; response.setHeader('Location', 'https://example.com/never-follow'); response.end();
    } else if (request.url === '/api/notes?page=2') {
      response.end(JSON.stringify({ data: [], meta: { total: 0, per_page: 15, current_page: 2, last_page: 2 } }));
    } else { response.statusCode = 404; response.end(JSON.stringify({ message: 'Not found.' })); }
  });
  const backendPort = await listen(backend);
  const reservation = createServer(); const port = await listen(reservation); await close(reservation);
  let output = '';
  const child = spawn(process.execPath, ['node_modules/next/dist/bin/next', 'start', '--hostname', '127.0.0.1', '--port', String(port)], { cwd: process.cwd(), env: { ...process.env, ELANG_BACKEND_URL: `http://127.0.0.1:${backendPort}`, NEXT_TELEMETRY_DISABLED: '1' }, stdio: ['ignore', 'pipe', 'pipe'] });
  child.stdout.on('data', chunk => { output += chunk; }); child.stderr.on('data', chunk => { output += chunk; });
  let backendClosed = false;
  t.after(async () => { if (child.exitCode === null) { child.kill('SIGTERM'); await once(child, 'exit'); } if (!backendClosed) await close(backend); });
  const origin = `http://127.0.0.1:${port}`;
  let ready = false;
  for (let attempt = 0; attempt < 70; attempt++) {
    if (child.exitCode !== null) break;
    try { const response = await fetch(origin, { signal: AbortSignal.timeout(500) }); if (response.status === 200) { ready = true; break; } } catch {}
    await delay(100);
  }
  assert.ok(ready, `Next.js did not start: ${output.slice(-2500)}`);
  await t.test('every Set-Cookie is preserved, with HttpOnly attributes and host scoping', async () => {
    const response = await fetch(origin + '/api/session'); assert.equal(response.status, 200);
    const cookies = response.headers.getSetCookie(); assert.equal(cookies.length, 2); assert.ok(cookies.every(cookie => /HttpOnly/i.test(cookie) && !/Domain=/i.test(cookie))); assert.ok(cookies.some(cookie => /Secure/.test(cookie)));
    assert.equal(response.headers.get('cache-control'), 'private, no-store'); assert.equal((await response.json()).csrfToken, 'csrf');
  });
  await t.test('login forwards cookie and CSRF, preserving the MFA response and cookie rotation', async () => {
    const response = await fetch(origin + '/api/session/login', { method: 'POST', headers: { Origin: origin, 'Content-Type': 'application/json', 'X-CSRF-Token': 'csrf', Cookie: 'elang_session=opaque' }, body: JSON.stringify({ _token: 'csrf', email: 'ada@example.com', password: 'example-only' }) });
    assert.equal(response.status, 202); assert.equal((await response.json()).mfaRequired, true); assert.match(response.headers.getSetCookie()[0], /rotated/);
    const call = received.at(-1); assert.equal(call.headers.cookie, 'elang_session=opaque'); assert.equal(call.headers['x-csrf-token'], 'csrf'); assert.equal(call.headers.origin, undefined); assert.equal(JSON.parse(call.body)._token, 'csrf');
  });
  await t.test('path/method/origin/content-type/size checks stop requests before the backend', async () => {
    const before = received.length;
    assert.equal((await fetch(origin + '/api/arbitrary-host')).status, 404);
    assert.equal((await fetch(origin + '/api/session/login')).status, 405);
    assert.equal((await fetch(origin + '/api/session/login', { method: 'POST', headers: { Origin: 'https://other.example', 'Content-Type': 'application/json' }, body: '{}' })).status, 403);
    assert.equal((await fetch(origin + '/api/session/login', { method: 'POST', headers: { Origin: origin, 'Content-Type': 'text/plain' }, body: '{}' })).status, 415);
    assert.equal((await fetch(origin + '/api/session/login', { method: 'POST', headers: { Origin: origin, 'Content-Type': 'application/json' }, body: 'x'.repeat(32769) })).status, 413);
    assert.equal(received.length, before);
  });
  await t.test('only the expected pagination value reaches the backend and redirects are not followed', async () => {
    const page = await fetch(origin + '/api/notes?page=2&target=https://other.example'); assert.equal(page.status, 200); assert.equal(received.at(-1).path, '/api/notes?page=2');
    assert.equal((await fetch(origin + '/api/notes?page=-1')).status, 400);
    const redirect = await fetch(origin + '/api/notes', { method: 'POST', headers: { Origin: origin, 'Content-Type': 'application/json', 'X-CSRF-Token': 'csrf' }, body: JSON.stringify({ title: 'Example', body: '' }) }); assert.equal(redirect.status, 502); assert.equal(redirect.headers.get('location'), null);
  });
  await t.test('backend outages return a bounded JSON error', async () => {
    await close(backend); backendClosed = true;
    const response = await fetch(origin + '/api/session'); assert.equal(response.status, 502); assert.equal((await response.json()).message, 'The Elang backend is unavailable. Please try again.');
  });
});
