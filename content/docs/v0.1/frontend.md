# React, Next.js, and other frontends

Elang can serve HTML or act as an API backend for React, Next.js, Vue, native mobile applications, and server clients. The official website uses HTML, CSS, and vanilla JavaScript. Your application can choose its own frontend.

## Start with the API application

```bash
composer create-project elang/api my-api dev-main
cd my-api
php elang serve
```

Register an account, verify it using the local [Studio mailbox](/docs/v0.1/studio), and create a token in `/dashboard`. Grant only the scopes your client needs. The working API includes private notes, validation, ownership checks, pagination, SSE, WebSocket capabilities, and `/openapi.json`.

## Allow your frontend origin

Set the exact frontend origin in `.env`:

```dotenv
APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://localhost:3000
```

The API starter's CORS middleware allows the listed origin and handles preflight requests. Multiple origins can be comma-separated. Avoid wildcards. It supports Authorization, Content-Type, Idempotency-Key, Last-Event-ID, and Accept headers. Tokens are sent in Authorization; cross-origin session cookies are not enabled. CORS controls browser access and does not replace authentication.

## Use fetch from any JavaScript framework

```javascript
const response = await fetch('http://127.0.0.1:8000/api/notes', {
  headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
});
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const { data, meta } = await response.json();
```

Use the pagination fields returned by the API. Handle 401 for expired/revoked tokens, 403 for insufficient scope, 404 for missing or another user's records, 422 for field errors, and 429 for rate limits.

## Use the included client with React

Copy `my-api/public/assets/elang-client.js` into your frontend's `src/lib/` directory. It is a dependency-free ES module using standard fetch. It is included in the Composer starter; no unpublished npm package is required.

```jsx
import { useEffect, useState } from 'react';
import { createClient } from './lib/elang-client.js';

export function Notes({ token }) {
  const [notes, setNotes] = useState([]);
  const [error, setError] = useState('');
  useEffect(() => {
    const controller = new AbortController();
    const api = createClient({
      baseUrl: 'http://127.0.0.1:8000',
      getToken: () => token,
    });
    api.request('GET', '/api/notes', { signal: controller.signal })
      .then(result => setNotes(result.data))
      .catch(error => { if (error.name !== 'AbortError') setError(error.message); });
    return () => controller.abort();
  }, [token]);
  if (error) return <p role="alert">{error}</p>;
  return <ul>{notes.map(note => <li key={note.id}>{note.title}</li>)}</ul>;
}
```

The example receives a token from your application's sign-in integration. Do not hardcode a real token in source control, publish it in a bundle, or put it in a URL. The account UI creates personal API tokens for this bearer-token example. For complete browser sign-in, registration, CSRF, and MFA flows, use the React/Next fullstack starters and session API. Elang is not an OAuth provider.

## Call Elang from Next.js on the server

Keep service credentials on the server. For user-specific applications, resolve a per-user credential from your server-side session instead of sharing one service token across users.

```jsx
import 'server-only';

export default async function Page() {
  const response = await fetch(`${process.env.ELANG_API_URL}/api/notes`, {
    headers: { Authorization: `Bearer ${process.env.ELANG_API_TOKEN}` },
    cache: 'no-store',
  });
  if (!response.ok) throw new Error(`Elang returned HTTP ${response.status}`);
  const { data } = await response.json();
  return <ul>{data.map(note => <li key={note.id}>{note.title}</li>)}</ul>;
}
```

Place `ELANG_API_URL` and `ELANG_API_TOKEN` in your server deployment's secrets. Do not prefix the token variable with `NEXT_PUBLIC_`. Server-to-server fetch does not require CORS. An API token accesses its owner's records only.

## Retry writes safely

```javascript
const key = crypto.randomUUID();
const created = await api.request('POST', '/api/notes', {
  json: { title: 'A durable note', body: 'Created from React' },
  idempotencyKey: key,
});
```

Reuse the same key only when retrying the same operation with identical input. The API starter commits the buffered JSON response, domain write, and event on the same database connection. A repeated matching request returns the saved response; different input with the same key returns 409. External services need their own idempotency handling. This middleware is opt-in when no header is supplied and does not claim exactly-once delivery across systems.

Continue with [real-time connections](/docs/v0.1/realtime) and [large application architecture](/guides/large-applications).
