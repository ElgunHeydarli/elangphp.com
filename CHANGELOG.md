# Changelog

## 0.1.0-dev — 2026-10-29

Initial development snapshot. Includes the application kernel, explicit routing/container API, modular services, CLI generators, and an English documentation website built on Elang with an HTML/CSS/JavaScript frontend.

This is not a stable release. See ROADMAP.md for unfinished areas and README.md for supported behavior.

### Learning and distribution expansion

Added a ten-lesson beginner path, setup/doctor guidance, a glossary, troubleshooting, FAQ, practical guides, and a complete session-backed task board. Added semantic metadata, JSON-LD, canonical redirects, sitemap modification dates, conditional HTML responses, social previews, and browser-local lesson completion.

Added a private-artifact-backed public Composer repository for the framework, application skeleton, installer, and task-board project. The global installer provides `elang new`; Composer create-project installs dependencies, generates the key, and runs doctor. Public installation does not require manually downloading or extracting source archives.

Added a narrow HTTP benchmark harness with a Laravel 13 fixture and explicit workload limits. It is not a claim of whole-framework or production superiority.

### Distributed application platform

Added Redis-backed cache, sessions, strict limiter state, distributed locks, transactional SQL-outbox notifications, and bounded Redis Stream wakeups with SQL replay reconciliation. Per-channel event sequences remain ordered through the outer business transaction. Locked audit and membership reads use current rows under MySQL REPEATABLE READ; a two-connection regression test and authenticated load workload cover the snapshot issue.

Added authenticator MFA, single-use recovery codes, maintained-library WebAuthn passkeys, revocable device sessions, credential-version rotation, private account security UI, and HMAC-chained audit scopes. Production error logs retain safe exception metadata without raw exception messages or request credentials.

Added explicit team roles/invitations and transaction-scoped tenant checks; private local/S3 files, authenticated signed download URLs, raster image re-encoding; private inbox notifications and encrypted queued email. Added private operations summaries, worker heartbeats, and optional manual OTLP traces with queue context propagation.

Added React and Next fullstack projects, a generated TypeScript OpenAPI client, cookie-session/CSRF API, and a SaaS workspace with optimistic project edits, scoped APIs, private files, invitations, notifications, and authenticated SSE. Distribution now defines eighteen Composer packages. Separate sanitized development repositories and a protected Packagist connection/submission UI are available; external indexing must be verified before claiming publication.

Added nine detailed documentation chapters in English, Azerbaijani, and Russian, corrected earlier limitations, added a private local development stack, and expanded CI to nine jobs including dependency minimum/latest, Redis, MySQL/PostgreSQL, frontend builds/proxy contracts, and a real S3-compatible Docker stack. Container secret files keep HTTP and docker-exec CLI processes on the same persistent development key.
