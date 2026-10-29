# Elang + Next.js notes frontend

A standalone Next.js App Router + TypeScript frontend for the Elang session API. It complements the PHP backend; the official Elang documentation site remains server-rendered HTML, CSS, and JavaScript.

## Run locally

Use Node.js 22.12 or newer and npm. Run the PHP backend separately, then in this directory:

```bash
cp .env.example .env.local
npm ci
npm run dev
```

Open **http://127.0.0.1:3000**. `ELANG_BACKEND_URL` defaults to `http://127.0.0.1:8000`. It is server-only, must be a trusted HTTP(S) origin without credentials or a path, and is never bundled into client JavaScript. `ELANG_ACCOUNT_URL` optionally chooses the public backend account link rendered at `/backend-account`. Never put secrets in a `NEXT_PUBLIC_` variable.

The Node route handler at `app/api/[...path]/route.ts` is an allowlisted same-origin proxy for the session and notes endpoints below. It forwards the request cookie and CSRF header, rejects foreign browser origins for writes, caps JSON bodies at 32 KiB, does not follow upstream redirects, and times out backend calls after 15 seconds. Destination hosts come only from server configuration, never a path, query parameter, or browser header.

Every backend `Set-Cookie` is forwarded separately, preserving HttpOnly, Secure, SameSite, Path, expiry, and deletion attributes. The Domain attribute is removed so the cookie attaches to the frontend host. API responses use `private, no-store`. Use HTTPS and backend cookie settings appropriate for your deployment; local HTTP requires local cookie settings on the backend.

The proxy checks unsafe browser origins locally and omits the Origin header on the server-to-server hop; the backend does not need a browser CORS exception for this same-origin setup. CSRF checks still apply to every write.

## Session and notes

This is a development-preview frontend example, not a production-readiness claim. It includes sign-in, registration when enabled, an authenticator/recovery-code step, email-verification status, and private note creation, editing, deletion, and pagination. Notes are rendered as text; ownership and access checks remain the backend’s responsibility.

The browser imports the real typed adapter in `lib/elang-client.ts`. Every request uses a relative `/api/…` URL with `credentials: 'include'`. The HttpOnly session cookie remains outside JavaScript. The CSRF token is kept in memory and sent as `X-CSRF-Token` on every unsafe request; session JSON also carries `_token`. The client refreshes `GET /api/session` after writes and 401 responses. It does not automatically replay failed writes. No bearer token or credential is stored in browser storage.

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
npm run build
npm run test:proxy
npm start
```

`npm run test:proxy` uses the existing build, starts temporary loopback frontend/mock-backend processes, verifies proxy boundaries/cookies, and stops them. `npm start` serves the built application on port 3000. For deployment, install the locked dependencies, build the application, and run the Next.js Node server behind your HTTPS ingress. The typecheck command generates Next.js route types first, so it also works on a fresh checkout.

Configure `ELANG_BACKEND_URL` at server runtime and keep the PHP backend private behind the Next.js public origin. Both applications need appropriate HTTPS, cookie, session, and trusted-host configuration. The frontend never replaces backend authentication, ownership checks, or validation.

Dependencies are pinned in `package-lock.json`: Next.js 16.4.3, React 19.3.1, and TypeScript 7.0.2. Requirements were checked against the [official Next.js installation guide](https://nextjs.org/docs/app/getting-started/installation), [response-cookie documentation](https://nextjs.org/docs/app/api-reference/functions/next-response#cookies), [official React versions](https://react.dev/versions), and official npm package metadata. No global installation is required.
