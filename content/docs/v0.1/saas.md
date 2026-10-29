# Build a SaaS workspace with Elang

The `elang/saas` reference application connects verified accounts, organizations, role checks, projects, private files, invitations, inbox notifications, scoped APIs, authenticated event streams, account security, and administrator operations. It is a runnable starting point for a team product. Billing, payment ledgers, and industry certification remain separate domain work.

## Install and run

```bash
composer create-project elang/saas workspace dev-main
cd workspace
php elang doctor
php elang serve
```

Composer's creation hooks generate an application key and prepare migrations/platform tables. For a source-generated project, run `composer install`, `php elang key:generate`, `php elang migrate`, and `php elang platform:setup` first. Keep the application root private and expose `public/` only.

Open `/register`, create a user, and verify the local account through `/_elang/studio/mail`. Open `/workspace`. Register a second account in a separate browser context to test invitation and isolation behavior. Use PostgreSQL or MySQL plus shared Redis for a multi-host deployment; SQLite and local storage are useful for development.

## Follow the first team journey

1. Create an organization with a short name. Its creator becomes the owner.
2. Add a project. Projects begin in `planned`; move them to `active` or `done`.
3. Invite another verified email as member or viewer. Share the generated private link with that person.
4. Accept the invitation while signed in as the matching email owner.
5. Confirm a viewer can read but cannot change projects or upload files.
6. Upload a private document or choose image processing for a JPEG/PNG/WebP.
7. Open the generated authenticated download. Remove the member and confirm their access fails.
8. Review inbox updates, account devices, and audit behavior.

Invitation links expire after seven days and are consumed once. Reissuing one replaces the older invitation for the same team/email. The sample displays a private link for intentional sharing; it does not send an external invitation email automatically.

## Understand project writes

`app/Projects.php` applies the same explicit tenant constraint to listing, update, and deletion. `Teams::within()` reloads membership and holds an organization lock through each SQL transaction. Project versions implement optimistic concurrency: an old form cannot silently overwrite a newer edit. A stale version returns 409 and asks the user to reload.

Project changes and their real-time event are committed together on one SQL connection. Event payloads contain a project identifier; consumers reload the current authorized snapshot. Audit entries include safe IDs/action names rather than project text. Projects, file metadata, notifications, and memberships use separate tables with explicit ownership.

The sample lists the latest 100 projects/files/inbox items. Add pagination, search, quotas, domain validations, and dedicated indexes for a larger product. The framework's model repository already supports pagination; do not mistake a bounded example screen for unlimited record retrieval.

## API and real-time clients

The SaaS application retains the Notes API and adds:

| Method | Path | Required scope |
| --- | --- | --- |
| GET / POST | `/api/teams` | `teams:read` / `teams:write` |
| GET / POST | `/api/teams/{team}/projects` | `teams:read` / `teams:write` |
| PUT / DELETE | `/api/teams/{team}/projects/{project}` | `teams:write` |

Cookie-authenticated writes require CSRF; scoped bearer tokens work for server/mobile integrations. A token scope does not replace membership/role checks. An outsider receives 404, and a read-only member cannot write even with a write-scoped token. PUT supplies title/status/integer version; DELETE supplies the current integer version as JSON. A valid Idempotency-Key protects identical JSON write retries on supported endpoints.

For browser updates, use authenticated SSE at `/workspace/team/{team}/events`. The stream releases its session lock and periodically rechecks current account version and membership. It closes after a bounded response window and must reconnect. Persist the event cursor and refresh the project list after a change. Beyond retained history, load a fresh snapshot. Team socket capabilities are not supplied by this sample; the separate Notes API demonstrates authenticated WebSockets for personal channels.

## Operate the application

Provision an administrator through a trusted terminal; the password prompt is hidden:

```bash
php elang user:create owner@example.com --admin --verified
php elang queue:work
php elang outbox:work
```

Use `/security` to enable an authenticator and register passkeys. Use `/operations` as a verified administrator to inspect queue state, failed job metadata, worker heartbeats, outbox backlog, and HTTP aggregates. Configure a real email transport before processing queued email. Run workers under a process supervisor and keep job registrations identical between web and worker releases.

Before production, test backup restoration, credential recovery, tenant isolation, concurrent edits, membership revocation, queue crash/retry behavior, storage reconciliation, rate limits, and realistic authenticated traffic. Define service-level objectives and measure the complete stack. Elang remains a development release; no unmeasured throughput or universal framework superiority is promised.

Related: [teams](/docs/v0.1/tenancy), [files](/docs/v0.1/files), [notifications](/docs/v0.1/notifications), [operations](/docs/v0.1/observability), and [release engineering](/docs/v0.1/releases).
