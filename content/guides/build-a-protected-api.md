# Build a protected Notes API

The API starter combines a working account interface with stateless bearer-token endpoints. Tokens have explicit scopes, expiry, revocation, and an owner. Notes are always scoped to that owner.

## Create the project

```bash
elang new notes-api --starter=api
cd notes-api
php elang serve
```

Register and verify your account using the local development mailbox. Open the account page and create a token after confirming your password. Copy the one-time value and keep it private.

## Read and write scopes

GET collection and item endpoints require notes:read. POST, PUT, and DELETE require notes:write. A read-only token cannot create or delete records. The server ignores submitted owner IDs and derives ownership from the authenticated user.

```bash
curl http://127.0.0.1:8000/api/notes \
  -H 'Authorization: Bearer YOUR_TOKEN'
```

Replace YOUR_TOKEN in your own terminal. Do not publish real tokens in examples or logs.

## Expected responses

A successful create returns 201 and Location. A successful delete returns 204. An invalid token returns 401, insufficient scope returns 403, and another user's record returns 404. Invalid JSON returns 400; invalid fields return 422; unsupported content types return 415.

The API accepts scoped bearer tokens or a verified browser session. Cookie-authenticated writes require X-CSRF-Token; supplying an invalid Authorization header does not fall back to session authentication.

## Test the boundaries

Create a read-only token and confirm a write is rejected. Create a token for a second account and confirm it cannot access the first account's note. Revoke a token and confirm the next request is unauthorized. Change the account password and confirm previously issued tokens stop working.

Before deployment, configure HTTPS, production environment settings, mail delivery, backups, and monitoring. A functional starter still needs the business rules and data retention requirements of your own product.
