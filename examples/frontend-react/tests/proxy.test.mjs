import test from 'node:test';
import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { setTimeout as delay } from 'node:timers/promises';
async function listen(server) { server.listen(0, '127.0.0.1'); await once(server, 'listening'); return server.address().port; }
async function close(server) { server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); }
test('Vite proxies only the API boundary and relays session cookies and CSRF', { timeout: 20_000 }, async t => {
  const calls = [];
  const backend = createServer(async (request, response) => {
    let body = ''; for await (const chunk of request) body += chunk;
    calls.push({ path: request.url, headers: request.headers, body });
    response.setHeader('Content-Type', 'application/json');
    response.setHeader('Set-Cookie', 'elang_session=opaque; Domain=backend.internal; Path=/; HttpOnly; SameSite=Lax');
    response.end(JSON.stringify({ user: null, csrfToken: 'csrf', registration: true, mailAvailable: true }));
  });
  const backendPort = await listen(backend); const reservation = createServer(); const port = await listen(reservation); await close(reservation);
  let output = '';
  const child = spawn(process.execPath, ['node_modules/vite/bin/vite.js', '--host', '127.0.0.1', '--port', String(port), '--strictPort'], { cwd: process.cwd(), env: { ...process.env, ELANG_BACKEND_URL: `http://127.0.0.1:${backendPort}` }, stdio: ['ignore', 'pipe', 'pipe'] });
  child.stdout.on('data', chunk => { output += chunk; }); child.stderr.on('data', chunk => { output += chunk; });
  t.after(async () => { if (child.exitCode === null) { child.kill('SIGTERM'); await once(child, 'exit'); } await close(backend); });
  const origin = `http://127.0.0.1:${port}`; let ready = false;
  for (let attempt = 0; attempt < 70; attempt++) { if (child.exitCode !== null) break; try { const response = await fetch(origin, { signal: AbortSignal.timeout(500) }); if (response.status === 200) { ready = true; break; } } catch {} await delay(100); }
  assert.ok(ready, `Vite did not start: ${output.slice(-2500)}`);
  const session = await fetch(origin + '/api/session'); assert.equal(session.status, 200); assert.match(session.headers.getSetCookie()[0], /HttpOnly/); assert.doesNotMatch(session.headers.getSetCookie()[0], /Domain=/i);
  const login = await fetch(origin + '/api/session/login', { method: 'POST', headers: { Origin: origin, Cookie: 'elang_session=opaque', 'X-CSRF-Token': 'csrf', 'Content-Type': 'application/json' }, body: JSON.stringify({ _token: 'csrf', email: 'ada@example.com', password: 'example-only' }) });
  assert.equal(login.status, 200); assert.equal(calls.at(-1).headers.origin, undefined); assert.equal(calls.at(-1).headers.cookie, 'elang_session=opaque'); assert.equal(calls.at(-1).headers['x-csrf-token'], 'csrf');
  const before = calls.length;
  assert.equal((await fetch(origin + '/api/arbitrary-host')).status, 404);
  assert.equal((await fetch(origin + '/api/session/login', { method: 'POST', headers: { Origin: 'https://other.example', 'Content-Type': 'application/json' }, body: '{}' })).status, 403);
  assert.equal(calls.length, before);
});
