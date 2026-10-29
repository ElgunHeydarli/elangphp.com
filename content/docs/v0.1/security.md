# Security & sessions

Elang provides security primitives and response defaults. Your application still needs a complete authentication, authorization, and deployment design.

## Passwords

```php
use Elang\Security\Password;

$hash = $passwords->hash($plainTextPassword);
$isValid = $passwords->verify($submittedPassword, $storedHash);
```

The service uses PHP's Argon2id password API. Use `needsRehash()` after successful verification to decide whether a stored hash needs updating. Ensure your PHP build supports Argon2id.

## Encryption

```php
use Elang\Security\Encryption;

$ciphertext = $encryption->encrypt('private value');
$plaintext = $encryption->decrypt($ciphertext);
```

Sodium secretbox authenticates ciphertext and uses a fresh nonce. A missing or invalid APP_KEY prevents encryption service creation. Keep the key safe; key rotation is a manual operation in this preview.

## Sessions and CSRF

Register SessionMiddleware followed by CsrfMiddleware. Inject `Elang\Security\Csrf` and call `$csrf->token($request)` while rendering a form. Include the token in a hidden `_token` field or in the `X-CSRF-Token` header.

Session cookies use HttpOnly and SameSite=Lax, plus Secure when the request is HTTPS. Regenerate the session ID after authentication with `$request->getSession()->migrate(true)`.

## Response protection

The kernel sets a restrictive Content Security Policy, frame denial, MIME sniffing protection, and a referrer policy. JSON and HTML errors carry request IDs. Production error responses hide internal exception messages.

## Report an issue

Do not post credentials or exploit details in a public issue. See SECURITY.md in the repository for the current reporting guidance. This preview has not undergone an independent security audit.


### Application-specific Content Security Policy

The framework supplies a restrictive same-origin CSP by default. An application may set an explicit `Content-Security-Policy` response header before the default headers are applied. Keep that policy server-controlled and limited to the route that needs it; never derive it from untrusted request headers. The official documentation site permits its configured analytics hosts only on public pages, after visitor consent; account and administrator pages keep the restrictive default.
