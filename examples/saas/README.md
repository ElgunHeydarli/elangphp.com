# Elang Workspace

A working SaaS reference application: verified accounts, MFA/passkeys, teams, roles, private invitations, versioned projects, scoped APIs, authenticated SSE, private uploads/downloads, inbox notifications, and administrator operations.

```bash
composer install
php elang key:generate
php elang migrate
php elang platform:setup
php elang doctor
php elang serve
```

Open /register, verify your local account through /_elang/studio/mail, then open /workspace. Create two different users and teams to exercise isolation. Provision an administrator through `php elang user:create owner@example.com --admin --verified`; the terminal hides the password.

For real-time events, connect EventSource to /workspace/team/TEAM_ID/events. Reconnect with the last event cursor and refresh the project snapshot after a change. The stream closes on revoked membership; a slow or expired replay requires a new snapshot.

Set a real MAILER_DSN before processing queued email with `php elang queue:work`. Invitation links are generated for private sharing; no third-party email is sent automatically. Configure Redis and run `php elang outbox:work` when sharing infrastructure across processes/hosts. Only expose public/ and keep secrets in .env.

This reference deliberately has no payment collection, subscription billing, regulatory certification, or universal capacity guarantee. Integrate those domain requirements with their own reviewed authorization, ledger, compliance, and recovery policies.

Detailed walkthrough: https://elangphp.com/docs/v0.1/saas
