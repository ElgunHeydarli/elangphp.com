# Development platform verification — 29 October 2026

These are observed development checks, not an independent security audit, banking certification, production capacity promise, or whole-framework comparison.

| Check | Observed result |
| --- | --- |
| PHP 8.3.30 + PostgreSQL 16.15 + Redis + real S3 service | 182 tests, 5,866 assertions; the MySQL-specific snapshot regression is intentionally skipped |
| PHP 8.4.24 + MariaDB 11.4.9 + Redis + real S3 service | 182 tests, 5,872 assertions, no skipped tests |
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
| Live documentation | All 210 public indexable pages checked at 390 px without horizontal overflow; official MCP SDK verified EN/AZ/RU documentation and localized citation URLs over public HTTPS |
| Live distribution | All 18 HTTPS package archive checksums verified; isolated global Composer installation and a SaaS project created from the public repository passed doctor |
| Private integration UI | EN/AZ/RU account/security/publishing/operations layouts at 390/1440; encrypted Packagist token storage and Authorization-header transport tested with a mock API |
| Deployment boundaries | Only public assets/front controller published; source, credentials, databases, runtime data, and developer Studio remain private |

Hosted [GitHub Actions run 40845236003](https://github.com/ElgunHeydarli/elangphp.com/actions/runs/40845236003), at source commit `4e433973e8c88e722de2699e2259ea66eda5b1d7`, passed all nine jobs: PHP 8.3/8.4, Redis, MySQL 8.4/PostgreSQL 17, minimum/latest dependency resolution, React/Next contracts/builds, and the complete Docker development stack with private S3 integration. The 182-test suite includes secret-file configuration and localized MCP citation regressions.

The sanitized [load result](benchmarks/results/2026-10-29-runtime.json) records the exact local workload: PHP 8.4.24, MariaDB 11.4.9, Redis 7.0.15, four PHP HTTP workers, two socket workers, CLI OPcache off, a deliberately raised test limiter, operations collection enabled. Its roughly 153 HTTP requests/second and 154 ms p95 describe only this short loopback experiment. Socket delivery p95 was about 467 ms for 50 local connections. These are not sustained capacity limits or a Laravel comparison.

The load experiment initially exposed duplicate audit sequences caused by a stale MySQL consistent-read snapshot after acquiring a lock. Current locking reads and a deterministic two-connection membership/audit regression fixed it; the repeated workload completed without unexpected errors.

Jobs/mail remain at least once with bounded leases/retries. SQL remains the durable job/replay authority; Redis coordinates wakeups. Object storage and SQL do not share a transaction. Independent security review, domain-level financial controls, backup/recovery rehearsals, and realistic deployment-specific capacity tests remain necessary. Production account email requires a verified real transport/sender. A Packagist submission is not confirmed indexing; verify the actual package metadata before changing installation claims.

## Launch closure review

- The canonical repository has no open issues or pull requests. All four site migrations are applied.
- A public HTTPS crawl checked 210 sitemap pages and 130 additional internal targets, including 14,541 link references and their fragments. No broken targets, missing fragments, canonical mismatches, empty titles, or accidental noindex directives were found.
- A fresh online SQLite backup was restored into an isolated database. All 28 table counts matched the completed snapshot; administrator role, migration state, HMAC audit integrity, and EN/AZ/RU pages passed. The restored database and temporary storage were removed after the exercise.
- The owned hosting mailbox `noreply@elangphp.com` was provisioned with a 256 MB quota. Validated TLS, SMTP authentication from the actual site runtime, and a real delivery into that same local mailbox passed. The test message was removed and no external recipient was contacted. The DSN remains in a private owner-only secret file.
- Six mail-DNS integration checks (48 assertions) cover the fixed domain/record scope, Authorization-header privacy, no credential persistence, idempotent retries, conflict preservation before writes, partial failures, administrator access, CSRF, HTTPS, current-password confirmation, and audit logging. The full local suite now has 190 tests and 5,890 assertions on PHP 8.3 and 8.4; seven optional-service tests are skipped in this final site-only pass. The earlier database/Redis/S3 evidence above remains specific to its recorded 182-test revision.
- The secure integration page offers reviewed Fornex SPF/DKIM/DMARC publication with a request-only API key. Actual external DNS publication and Packagist submission still require their respective account credentials; neither is claimed complete without provider verification.
- The public security contact is distributed at `/.well-known/security.txt`; its host-specific Nginx include limits caching to one hour and its disclosure metadata expires on 29 April 2027.

The publisher now rejects a release whose runtime directory is not connected to the reviewed shared storage before changing any public file or release pointer. Two isolated host-layout regressions (eight assertions) verify rejection and successful publication with the common bootstrap. This guard follows a release-staging error that was rolled back, corrected, and rechecked under the real site operator account.
