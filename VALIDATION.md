# Development snapshot verification — 29 October 2026

This records checks performed for the current implementation. It is not an independent security audit, a banking certification, a production capacity guarantee, or evidence of general superiority over another framework.

| Check | Observed result |
| --- | --- |
| PHP 8.3.30, including isolated PostgreSQL 16.15 integration | 147 tests, 3,953 assertions passed |
| PHP 8.4.24, including isolated MariaDB 11.4.9 / PDO MySQL integration | 147 tests, 3,953 assertions passed |
| Competing database workers | Four processes claimed and acknowledged 20 jobs without duplicate claims on each tested server database |
| Composer validation and advisory audit | Valid metadata; no known dependency advisories at check time |
| PHPStan | Level 5, no errors |
| Composer distributions | Nine packages; archive checksums and exclusion of keys, databases, vendor, and runtime files checked |
| Clean terminal installations | Base, task board, auth, blog, dashboard, and API applications installed; public HTTPS installer with `--starter=api` passed |
| Starter behavior | Account pages, database operations, validation, ownership restrictions, API scopes, idempotency, and event publication checked |
| Real-time transports | Live fetch SSE, WebSocket ping/commands/event broadcast, denied write scope, and rejected foreign Origin passed |
| Documentation MCP | Official JavaScript client SDK initialized, searched, read documents, and listed resources against the public HTTPS endpoint |
| Multilingual pages | 183 canonical pages; every page rendered at 390px without page overflow; EN/AZ/RU content and reciprocal language links checked |
| Language control | Local flag images, same-page switching, language-aware search, keyboard dismissal, and desktop/mobile behavior passed |
| Account deployment | Initial administrator created through a hidden terminal prompt; real HTTPS login enforces password rotation before editor access |
| Private boundaries | `.env`, `.git/config`, source, vendor, and production Studio URLs return 404 |
| SEO and updates | Canonicals, hreflang, JSON-LD, one primary heading, sitemap, localized Atom feeds, and published-only search/MCP coverage checked |

GitHub Actions includes PHP 8.3/8.4 checks and MySQL 8.4/PostgreSQL 17 service jobs. Those hosted jobs require the source to be pushed; the local database checks above used the stated MariaDB and PostgreSQL versions.

Operational limits remain documented: JSON jobs use at-least-once delivery and a fixed lease; real-time history uses SQL polling; cache, limiter, locks, and sessions use the configured filesystem by default. Workload-specific scaling and independent review remain necessary for critical deployments. Production account email requires an actual mail transport and sender configuration.
