# Authentication and account security

Elang's Auth Starter includes registration, session login, verification, password recovery, profile updates, password rotation, and scoped API tokens. Enable it with AuthProvider; the starter already does that for you.

## Create an authenticated application

```bash
elang new my-app --starter=auth
cd my-app
php elang serve
```

After one-time installer setup, this command creates the application through Composer. Dependencies, the application key, schema migrations, and doctor checks are part of project setup. Open `/register` to create your own account. No shared administrator password is included.

## Sessions and protected routes

AuthProvider groups browser account routes behind session, CSRF, and rate-limit middleware. Login regenerates the session identifier and CSRF token. Logout invalidates the session. Request bodies and authorization values are not recorded by Elang Studio.

Use RequireAuth on private browser routes after SessionMiddleware. It sets the current User in the `_user` request attribute. A temporary password redirects the user to `/password` before private features can be used.

```php
$router->get('/private', fn () => ['message' => 'Signed in'])
    ->middleware(SessionMiddleware::class, RequireAuth::class);
```

Use the complete middleware group shown in the starter for state-changing forms, including CsrfMiddleware. A protected route still needs application-specific ownership or policy checks.

## Passwords and recovery

Public passwords require at least 12 characters and support long passphrases. The stored value is an Argon2id hash. A trusted terminal can create a temporary password that must be changed at first sign-in.

Recovery and verification links use random one-time tokens. Only token hashes are stored in the database, with purpose and expiry. A successful password change invalidates other sessions and revokes API tokens. Resetting a password does not silently sign the user into a new browser.

## Local email and production email

With APP_ENV=local and the null mail transport, account messages are stored encrypted in the private development mailbox. Open `/_elang/studio/mail` locally to use a verification or reset link.

In production, configure MAILER_DSN and MAIL_FROM. A null transport does not claim to deliver account mail; recovery reports that service is unavailable. The local mailbox and Studio are not exposed in production.

## Authorization and roles

Registration always creates a normal user. Submitted role fields are not accepted. Trusted provisioning can create an administrator, and RequireAdmin also requires email verification. Gate lets an application define explicit policies:

```php
$gate->define('post.update', fn (User $user, Post $post) =>
    $post->author_id === $user->id
);
$gate->authorize('post.update', $user, $post);
```

Scope database queries by the current user's identifier, including reads, updates, and deletes. The complete blog, workspace, and API starters demonstrate this boundary.

## API tokens

Create tokens from the account page after verification and password confirmation. The raw token is displayed once. Database rows store a SHA-256 digest, explicit scopes, owner, and expiry. BearerAuth authenticates a token; RequireScope checks the route's `_scope` default. Token ownership never comes from a request body.
