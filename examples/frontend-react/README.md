# Elang + React notes frontend

A standalone Vite + React + TypeScript frontend for the Elang session API. It complements the PHP backend; the official Elang documentation site remains server-rendered HTML, CSS, and JavaScript.

## Run locally

Use Node.js 22.12 or newer and npm. Run the PHP backend separately, then in this directory:

```bash
cp .env.example .env
npm ci
npm run dev
```

Open **http://127.0.0.1:5173**. Vite listens on the loopback interface and keeps the port fixed. `ELANG_BACKEND_URL` is read only by the Vite configuration, defaults to `http://127.0.0.1:8000`, and must be a trusted HTTP(S) origin. API paths are allowlisted and proxied without changing their `/api` prefix. The proxy relays cookies and removes a backend Domain attribute so cookies attach to the frontend host.

`VITE_ELANG_ACCOUNT_URL` is an optional **public** link for the backend account page. Do not put secrets into any `VITE_` variable; Vite includes those values in browser assets.

The proxy checks unsafe browser origins locally and omits the Origin header on the server-to-server hop; the backend does not need a browser CORS exception for this same-origin setup. CSRF checks still apply to every write.

## Session and notes

This is a development-preview frontend example, not a production-readiness claim. It includes sign-in, registration when enabled, an authenticator/recovery-code step, email-verification status, and private note creation, editing, deletion, and pagination. Notes are rendered as text; ownership and access checks remain the backend’s responsibility.

The browser imports the real typed adapter in `src/lib/elang-client.ts`. Every request uses a relative `/api/…` URL with `credentials: 'include'`. The HttpOnly session cookie remains outside JavaScript. The CSRF token is kept in memory and sent as `X-CSRF-Token` on every unsafe request; session JSON also carries `_token`. The client refreshes `GET /api/session` after writes and 401 responses. It does not automatically replay failed writes. No bearer token or credential is stored in browser storage.

Start an Elang backend with the session endpoints enabled on `http://127.0.0.1:8000`. The backend must support:

| Endpoint | Contract |
| --- | --- |
| `GET /api/session` | `{user: null \| {id,name,email,verified,role}, csrfToken, registration, mailAvailable}` |
| `POST /api/session/login` | JSON `{_token,email,password}`; 200 signed-in user state or 202 `{mfaRequired:true,csrfToken}` |
| `POST /api/session/register` | JSON `{_token,name,email,password,password_confirmation}` |
| `POST /api/session/mfa` | JSON `{_token,code}`; authenticator or recovery code |
| `POST /api/session/logout` | JSON `{_token}` |
| `GET /api/notes?page=1` | `{data,meta:{total,per_page,current_page,last_page}}` |
| `POST /api/notes` | JSON `{title,body}`; 201 `{data}` |
| `PUT /api/notes/{id}` | JSON `{title,body}`; 200 `{data}` |
| `DELETE /api/notes/{id}` | 204 |

All unsafe requests include the CSRF header. Note endpoints require a verified account and operate on that account’s own records. Validation errors may return `{message,errors:{field:[message]}}`. Titles allow 160 characters and note bodies 5,000. Search filters only the current page, as its input label explains.

`/backend-account` explains how to open the backend account UI for email verification, password recovery, authenticator enrollment, recovery codes, and profile settings. Cookies belong to their host; separate frontend/backend hostnames may require a separate backend sign-in. Use the same loopback hostname consistently during local development. Configure a real backend mail transport for email delivery, or use the backend’s local Studio mailbox during development.

The UI uses inline SVG, CSS, and a small SVG favicon. Its optional Google Fonts stylesheet falls back to system/Georgia fonts when unavailable.

## Check and build

```bash
npm run typecheck
npm test
npm run test:proxy
npm run build
```

`npm run test:proxy` starts temporary loopback Vite/mock-backend processes, checks the API boundary and cookie/CSRF relay, then stops them.

`npm run preview` serves the build at `http://127.0.0.1:4173` with the same development proxy. Vite’s development/preview server is not a production application server. For deployment, serve `dist/` behind your own HTTPS server, route the allowlisted `/api/session` and `/api/notes` paths to Elang on the same public origin, and configure SPA fallback for `/backend-account`. The PHP backend needs compatible trusted-host, cookie, session, and HTTPS settings.

Dependencies are pinned in `package-lock.json`: React 19.3.1, Vite 8.3.5, and TypeScript 7.0.2. Requirements were checked against the [official React versions](https://react.dev/versions), [Vite getting-started guide](https://vite.dev/guide/), [Vite proxy configuration](https://vite.dev/config/server-options#server-proxy), and official npm package metadata. No global installation is required.
