/** Elang fetch client. Works in browsers, React, Next.js, and Node with fetch. */
export function createClient({ baseUrl, getToken, fetch: requestFetch = globalThis.fetch }) {
  const base = new URL(baseUrl);
  if (!['https:', 'http:'].includes(base.protocol)) throw new TypeError('Use an HTTP(S) API URL.');
  const urlFor = path => { if (!path.startsWith('/') || path.startsWith('//')) throw new TypeError('Use an API path beginning with one /.'); const url = new URL(path, base); if (url.origin !== base.origin) throw new TypeError('API paths must stay on the configured origin.'); return url; };
  const headersFor = async extra => {
    const token = await getToken();
    if (typeof token !== 'string' || !token) throw new Error('An API token is required.');
    return { Accept: 'application/json', Authorization: `Bearer ${token}`, ...extra };
  };
  const request = async (method, path, { json, idempotencyKey, signal } = {}) => {
    const headers = await headersFor(json === undefined ? {} : { 'Content-Type': 'application/json' });
    if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey;
    const response = await requestFetch(urlFor(path), { method, headers, signal, body: json === undefined ? undefined : JSON.stringify(json), credentials: 'omit' });
    const data = response.status === 204 ? null : await response.json();
    if (!response.ok) { const error = new Error(data.message || `HTTP ${response.status}`); error.status = response.status; error.errors = data.errors; error.requestId = data.request_id; throw error; }
    return data;
  };
  const events = async (path, { onEvent, signal, after = '0' }) => {
    let cursor = String(after);
    while (!signal?.aborted) {
      const response = await requestFetch(urlFor(path), { headers: await headersFor({ Accept: 'text/event-stream', 'Last-Event-ID': cursor }), signal, credentials: 'omit' });
      if (!response.ok || !response.body) throw new Error(`Event stream returned HTTP ${response.status}.`);
      const reader = response.body.getReader(); const decoder = new TextDecoder(); let pending = '';
      try {
        while (!signal?.aborted) {
          const { value, done } = await reader.read(); if (done) break;
          pending += decoder.decode(value, { stream: true }).replaceAll('\r\n', '\n');
          let split;
          while ((split = pending.indexOf('\n\n')) !== -1) {
            const frame = pending.slice(0, split); pending = pending.slice(split + 2);
            const fields = {}; for (const line of frame.split('\n')) { if (line.startsWith(':')) continue; const index = line.indexOf(':'); if (index < 0) continue; const name = line.slice(0, index); const value = line.slice(index + 1).replace(/^ /, ''); fields[name] = name === 'data' && fields.data ? `${fields.data}\n${value}` : value; }
            if (fields.data) { const data = JSON.parse(fields.data); await onEvent({ id: fields.id || cursor, event: fields.event || 'message', data }); if (fields.id) cursor = fields.id; }
          }
          if (pending.length > 65536) throw new Error('Event frame is too large.');
        }
      } finally { await reader.cancel().catch(() => {}); reader.releaseLock(); }
      if (signal?.aborted) return;
      await new Promise(resolve => { const timer = setTimeout(done, 1500); const stop = () => { clearTimeout(timer); done(); }; function done() { signal?.removeEventListener('abort', stop); resolve(); } signal?.addEventListener('abort', stop, { once: true }); });
    }
  };
  const socket = async ({ after, url: socketBase, WebSocket: Socket = globalThis.WebSocket } = {}) => {
    const { token, path } = await request('POST', '/api/realtime-token');
    const url = socketBase === undefined ? urlFor(path) : new URL(path, socketBase);
    if (!['http:', 'https:', 'ws:', 'wss:'].includes(url.protocol) || url.username || url.password) throw new TypeError('Use an HTTP(S) or WS(S) socket origin.');
    url.protocol = ['https:', 'wss:'].includes(url.protocol) ? 'wss:' : 'ws:';
    if (after !== undefined) url.searchParams.set('after', String(after));
    return new Socket(url, ['elang', `token.${token}`]);
  };
  return { request, events, socket };
}
