# React, Next.js, and generated TypeScript clients

Elang is a PHP backend. Its official documentation website stays server-rendered HTML, CSS, and vanilla JavaScript. Your application frontend can use React, Next.js, another JavaScript framework, mobile code, or a server-side client.

## Create a complete frontend/backend project

The self-hosted Composer repository provides terminal installation. These are development packages, not a claimed stable release or a Packagist listing.

```bash
composer create-project --repository=https://elangphp.com/packages elang/react my-react-app 0.1.0-dev
# Alternatively:
composer create-project --repository=https://elangphp.com/packages elang/next my-next-app 0.1.0-dev
```

A configured global installer also accepts `elang new my-app --starter=react` or `--starter=next`. Composer prepares the PHP application. The frontend sources live under `frontend/`; no manual ZIP download is required. Requirements: PHP 8.3+, Composer 2, and Node 22.12+ for these frontend starters.

```bash
cd my-react-app
php elang serve
```

In a second terminal:

```bash
cd my-react-app/frontend
npm ci
npm run dev
```

Open the frontend URL printed by Vite or Next. Keep the backend running on its printed address. Copy `frontend/.env.example` to the frontend's documented local environment file when changing the backend address. `ELANG_BACKEND_URL` is server-side proxy configuration and must never contain public browser secrets.

## How authentication works

Both starters use same-origin `/api` proxies. The browser holds an HttpOnly session cookie; bearer tokens are not saved to localStorage. `GET /api/session` returns the current user, a CSRF token, registration availability, mail availability, and whether a second factor is pending. Unsafe requests include `X-CSRF-Token`.

1. Load the session before submitting a form.
2. Register or log in through `/api/session/register` or `/api/session/login`.
3. If `mfaRequired` is true, submit a code to `/api/session/mfa`; account access remains pending until it succeeds.
4. Refresh session state after a write, because sign-in/out rotates the session/CSRF token.
5. Create, edit, list, and delete your private notes.

New accounts must verify their email before protected API writes. In local development, open the backend's `/_elang/studio/mail` to inspect the encrypted development mailbox. Cookies are scoped by host/domain and path, not by port; using different frontend/backend hostnames may require a separate backend login. The starter's `/backend-account` page explains this handoff.

## Proxy boundaries

The Next proxy forwards only explicit allowed API paths/methods, caps JSON requests at 32 KiB, uses a bounded timeout, refuses upstream redirects, and relays individual Set-Cookie headers. Unsafe browser requests must come from the frontend's own origin. The proxy strips that browser Origin before its server-to-server request so the backend does not confuse proxy traffic with a direct CORS request.

Vite's development proxy applies the same-origin check for unsafe calls. Deploy a reviewed reverse proxy or application proxy in production; a Vite development server is not a production deployment. Never expose a general-purpose upstream URL parameter, forward arbitrary browser authorization to unrelated hosts, or disable CSRF to make a proxy work.

For a browser talking directly to a different API origin, configure explicit CORS origins and a reviewed credential/cookie policy. CORS is not authentication. The supplied starters avoid cross-site cookies by using a same-origin proxy. Server/mobile clients can use scoped bearer tokens instead.

## Generate the TypeScript contract

The Notes starter publishes `/openapi.json` from `resources/openapi.php`. Generate a standalone typed client from this trusted local schema:

```bash
php elang api:types --schema=resources/openapi.php --output=frontend/src/lib/generated.ts
# Next.js starter:
php elang api:types --schema=resources/openapi.php --output=frontend/lib/generated.ts
```

The generated module contains schema types, operation inputs/outputs, routes, and `createApiClient()`. The starter imports these types and uses generated operations for note writes. Edit the OpenAPI document, regenerate, typecheck, and review the resulting diff when changing a backend contract.

```ts
import { createApiClient } from './generated.ts';

const api = createApiClient();
const result = await api.request('createNote', {
  body: { title: 'First idea', body: 'A useful note' },
  headers: { 'X-CSRF-Token': csrfToken },
});
```

The generator deliberately supports a bounded OpenAPI 3.1 subset: local component references, objects, arrays, scalar/null types, enums, unions, JSON request bodies, and JSON/SSE response schemas. Unsupported schema constructs fail explicitly. TypeScript types do not validate untrusted runtime JSON. SSE operations describe the contract; use EventSource/stream handling for a live stream rather than a JSON-oriented convenience call.

## Verify and deploy

```bash
cd frontend
npm run typecheck
npm test
npm run build
```

Next also provides `npm run test:proxy`. Run the PHP suite and a browser journey against the real backend: sign in, verify, complete MFA where enabled, create/edit/delete, refresh, sign out, and attempt a cross-user read. Review CSP, HTTPS, trusted proxy headers, backend URL configuration, cookie domain, timeouts, and upstream availability in your own deployment.

The frontend lockfiles pin the tested dependency tree. Security updates still require review and repeat validation. See [API authentication](/docs/v0.1/authentication), [account security](/docs/v0.1/account-security), and [testing](/docs/v0.1/testing).
