# A narrow, reproducible HTTP comparison

This measures the same small stateless JSON route through Elang and Laravel on a single-worker PHP development server. It is a baseline for request overhead, not a production benchmark or evidence of overall framework superiority. No database, template, session, authentication, queue, or application workload is included. No Laravel route/config caching or Octane is used.

Use PHP 8.4 for **both servers** because the Laravel 13 fixture requires it. Both should use the same OPcache setting. The following example explicitly disables CLI OPcache for both.

1. Install Elang dependencies at the repository root with Composer.
2. Install the isolated Laravel fixture using the same PHP binary: `php8.4 /path/to/composer install --working-dir=benchmarks/laravel --no-dev --no-interaction --prefer-dist`.
3. In separate terminals, from the repository root, start:

```bash
php8.4 -d opcache.enable_cli=0 -S 127.0.0.1:8098 benchmarks/elang/index.php
php8.4 -d opcache.enable_cli=0 -S 127.0.0.1:8099 benchmarks/laravel/index.php
```

4. Run `php8.4 benchmarks/compare.php --requests=500 --rounds=3 --output=/tmp/elang-http-comparison.json`.
5. Stop the servers with Ctrl+C. Keep the raw JSON and record hardware, OS, PHP SAPI, dependency lock files, server settings, and system load alongside the result.

The client warms each fixture, alternates execution order, verifies the exact status and response body, and records median/p95 latency and sequential requests per second. If an endpoint fails or rate limits, the comparison aborts rather than treating its error response as a fast success.

Do not run these fixtures on a public interface. Do not compare this output with unrelated benchmark numbers, or claim it represents feature parity. A production decision needs a representative workload, the intended server/runtime, realistic concurrency, resource measurements, and application-specific tests.

## Authenticated SQL and WebSocket workload

`runtime-load.mjs` (Node 22+) targets a disposable SaaS application on loopback. Supply a private `ELANG_BENCH_FIXTURE` JSON file containing `url`, `run`, `actors` (each with its own `team` and bearer `token`), `runtime`, and `configuration`. Optional `socket` and `socketToken` enable real fan-out. Never commit the fixture or real credentials. Set `ELANG_BENCH_OUTPUT` to a sanitized report path.

The harness warms the service, runs concurrent tenant-scoped project create/update/delete transactions, checks cross-tenant denial, verifies every expected response status, and optionally measures delivery to many authenticated WebSocket connections. Request counts include expected 404 isolation checks. Configure a deliberate test rate limit and record it: counting throttled responses as successful work is invalid. Record SQL/PHP/Redis versions and worker counts in the fixture's public configuration fields, never DSNs or passwords.

```bash
ELANG_BENCH_FIXTURE=/absolute/private/disposable-fixture.json \
ELANG_BENCH_ROUNDS=80 ELANG_BENCH_CONCURRENCY=8 ELANG_BENCH_SOCKETS=50 \
ELANG_BENCH_OUTPUT=/tmp/elang-runtime-report.json node benchmarks/runtime-load.mjs
```

Stop the owned servers/relay after testing and remove only the disposable databases, users, and Redis namespace you created. This source harness does not provision production infrastructure. The test suite separately covers competing queue reservations, expired leases and stale acknowledgments, transactional rollback, committed event order, Redis relay deduplication, real private S3 operations, and local-only SMTP delivery.
