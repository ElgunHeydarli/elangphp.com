# Development platform verification — 29 October 2026

These are observed development checks, not an independent security audit, banking certification, production capacity promise, or whole-framework comparison.

| Check | Observed result |
| --- | --- |
| PHP 8.3.30 + PostgreSQL 16.15 + Redis + real S3 service | 180 tests, 5,822 assertions; the MySQL-specific snapshot regression is intentionally skipped |
| PHP 8.4.24 + MariaDB 11.4.9 + Redis + real S3 service | 180 tests, 5,828 assertions, no skipped tests |
| Shared Redis | Cache, lock exclusion, session persistence, strict shared limits, SQL rollback/outbox deduplication, and stale session write/delete rejection passed |
| SQL concurrency | Four competing queue workers, fenced lease recovery, committed channel ordering, current membership/audit reads under MySQL REPEATABLE READ passed |
| Real SMTP | Encrypted queued message delivered through a private loopback SMTP capture; no external recipient contacted |
| Real S3-compatible service | Checksum-verified SeaweedFS 4.51; signed SDK write/read/delete, anonymous denial, invalid credentials, and metadata failure behavior passed |
| Telemetry | Real SDK spans and dispatch-to-worker W3C trace context; request URL/query/credential privacy checked |
| Composer/PHPStan | Strict metadata validation, level 5 analysis, and advisory audit passed |
| Distribution | Eighteen packages; clean Composer create-project/doctor for all nine applications; remaining profiles/framework/runtime/installer resolved in a fresh consumer |
| Global installer | Isolated Composer home; elang new with SaaS starter and doctor passed |
| React and Next | Typechecks, ten client tests, Next proxy integration, production builds, and dependency audits passed |
| Real frontend journeys | React and Next login, generated-client create/update/delete, reload persistence, logout against actual Elang PHP backend passed |
| Browser account security | Passkey registration/discoverable login/UV rejection; authenticator setup, recovery login, other-session revocation, responsive layouts passed |
| Real-time transport | Live SSE, idempotent API writes, WebSocket command/result/broadcast, denied write scope/Origin, and active socket credential revocation passed |
| Authenticated load | 320 SQL-backed project requests, eight concurrent clients, four tenants, 80 expected cross-tenant denials, zero unexpected errors; 50/50 socket subscribers received a real event |
| Multilingual content | Nine new chapters per language; executable fences, inline identifiers, localized search, reciprocal canonical/hreflang, metadata, feeds, and sitemap checks passed |
| Private integration UI | EN/AZ/RU account/security/publishing/operations layouts at 390/1440; encrypted Packagist token storage and Authorization-header transport tested with a mock API |
| Deployment boundaries | Only public assets/front controller published; source, credentials, databases, runtime data, and developer Studio remain private |

Hosted [GitHub Actions run 40842095232](https://github.com/ElgunHeydarli/elangphp.com/actions/runs/40842095232) passed all nine jobs: PHP 8.3/8.4, Redis, MySQL 8.4/PostgreSQL 17, minimum/latest dependency resolution, React/Next contracts/builds, and the complete Docker development stack with private S3 integration. The additional secret-file unit check passed after that development work.

The sanitized [load result](benchmarks/results/2026-10-29-runtime.json) records the exact local workload: PHP 8.4.24, MariaDB 11.4.9, Redis 7.0.15, four PHP HTTP workers, two socket workers, CLI OPcache off, a deliberately raised test limiter, operations collection enabled. Its roughly 153 HTTP requests/second and 154 ms p95 describe only this short loopback experiment. Socket delivery p95 was about 467 ms for 50 local connections. These are not sustained capacity limits or a Laravel comparison.

The load experiment initially exposed duplicate audit sequences caused by a stale MySQL consistent-read snapshot after acquiring a lock. Current locking reads and a deterministic two-connection membership/audit regression fixed it; the repeated workload completed without unexpected errors.

Jobs/mail remain at least once with bounded leases/retries. SQL remains the durable job/replay authority; Redis coordinates wakeups. Object storage and SQL do not share a transaction. Independent security review, domain-level financial controls, backup/recovery rehearsals, and realistic deployment-specific capacity tests remain necessary. Production account email requires a verified real transport/sender. A Packagist submission is not confirmed indexing; verify the actual package metadata before changing installation claims.
