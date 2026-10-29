# Account security: MFA, passkeys, and devices

This guide extends [authentication](/docs/v0.1/authentication). Elang combines password sign-in, optional time-based one-time passwords, passkeys, revocable device sessions, and a tamper-evident audit chain. These features are application building blocks; they do not certify an application for banking or regulatory compliance.

## Install and prepare

Use PHP 8.3 or newer, Composer 2, a stable application encryption key, HTTPS, and synchronized server time. The full framework includes OTPHP, web-auth/webauthn-lib, and a local SVG QR renderer. No third-party QR service receives your secret.

```bash
php elang key:generate
php elang platform:setup
php elang doctor
```

For an existing production database, back it up, review schema changes, and run `php elang platform:setup --force`. Keep the existing `APP_KEY`: changing it makes stored MFA secrets and encrypted mail unreadable. Add `Elang\Auth\AuthProvider` in bootstrap if your application does not already register it.

## Enable an authenticator

1. Sign in, update any temporary password, and verify your email.
2. Open `/security`, enter your current password, and begin setup.
3. Scan the local QR image with an authenticator, or enter the displayed setup key manually.
4. Submit a current six-digit code. Setup expires after ten minutes.
5. Store the ten recovery codes in a private password manager. They are displayed once.

The database stores an encrypted authenticator secret and hashes of recovery codes. Codes use six digits and 30-second steps, with a one-step clock tolerance. An atomic last-step update rejects reuse of an accepted authenticator code. Each recovery code can be consumed once. A code used to confirm setup cannot immediately be reused for sign-in.

After MFA is enabled or disabled, other sessions, API tokens, and outstanding password/verification links are revoked. The current browser receives a fresh session. Password sign-in then creates a five-minute pending challenge; it does not grant account access until the second factor succeeds. `/two-factor` handles browser forms, and `/api/session/mfa` handles JSON clients. Both have CSRF and attempt limits.

Disabling MFA and replacing recovery codes require the current password and a fresh authenticator or recovery code. Replacing recovery codes invalidates every old code. Email password recovery changes the password and revokes sessions; it does not remove MFA.

## Register and use passkeys

In `/security`, enter your password, choose a device name, and register a passkey. The browser uses its native credential dialog. User verification and a discoverable credential are required. Elang supports ES256 and RS256 public-key algorithms and `none` attestation. Attestation proves no device certification here.

The relying-party ID comes from the hostname of `APP_URL`. Production needs HTTPS. Loopback and localhost are supported for development. Configure exact origins when multiple frontends use the same relying-party hostname:

```php
'auth' => [
    'passkey_origins' => ['https://app.example.com'],
],
```

Origins include scheme, hostname, and port. Broad wildcard/subdomain acceptance is disabled. An origin must also satisfy the browser's relying-party rules: adding an unrelated domain to this list does not bypass them. Registration/authentication challenges are random, bound to the server session, expire after two minutes, and are consumed once. The maintained WebAuthn library verifies signatures, origin, challenge, user verification, and counter rules. Credential updates use optimistic concurrency.

Use “Sign in with a passkey” on `/login`. A verified passkey is a separate sign-in method; it does not ask for the password's TOTP challenge. Removing a passkey requires the account password. Keep another recovery method before removing the last usable credential.

## Review devices and audit records

`/security` lists device sessions with their browser description and last activity. Descriptions are hints supplied by browsers, not trusted device identity. Revoking one session takes effect on its next authorization check. Changing a password revokes all sessions and API/link tokens. Native session cookies are HttpOnly, SameSite=Lax, and Secure on HTTPS. Redis deployments share revocation data through SQL and session bytes through Redis.

```php
$audit = $app->container->get(Elang\Audit\Audit::class);
$result = $audit->verify('user:'.$user->id);
// ['valid' => true, 'sequence' => ..., 'hash' => ...]
```

Audit entries form a per-scope HMAC chain under a key derived from `APP_KEY`. They detect row changes, missing sequence entries, and a changed head while the application key is trusted. They are not an external immutable log. Export signed checkpoints to a separate trust boundary if your threat model requires database-administrator resistance. Never record passwords, secrets, tokens, email addresses, request bodies, or payment data in audit metadata.

## Troubleshooting

- **Code rejected:** synchronize server/device time; wait for a new code if the previous step was already consumed. Never increase the acceptance window to hide clock problems.
- **Passkey dialog unavailable:** check HTTPS/localhost, browser support, exact origin, RP hostname, and platform authentication availability.
- **Setup disappeared:** the ten-minute setup or two-minute browser challenge expired; restart setup.
- **Every device signed out:** password or factor changes intentionally rotate credential versions.
- **Lost all factors:** use a saved recovery code. Administrative recovery must be a separately reviewed identity-verification process; Elang supplies no public MFA bypass endpoint.

Next: [Redis infrastructure](/docs/v0.1/redis) and [operations](/docs/v0.1/observability).
