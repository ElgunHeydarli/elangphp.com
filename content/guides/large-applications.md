# Design a large application with Elang

A bank, marketplace, startup platform, and public API have different correctness and operating requirements. Elang supplies HTTP, data access, transactions, account authentication, API tokens, policies, background jobs, scheduling, and real-time transports. Application architecture and operating evidence determine whether a particular system is ready for its workload.

## Split the business into modules

Organize modules such as Accounts, Orders, Payments, and Reporting under `app/`. Keep controllers thin. Put business operations in injected services and register integrations through service providers. Keep authorization close to the operation so HTTP, jobs, and socket commands enforce the same rule.

Use `Gate` for application policies, owner-scoped repositories for user data, and explicit SQL constraints for invariants. A display role alone is not a complete permission model. Tenant IDs must come from trusted authentication and must be applied to every relevant query, unique key, job, and event channel.

## Make changes atomic

Use `Database::connection()->transactional()` around operations that must commit together. Elang's queue and event store can participate when they use that same connection. Keep transactions short. Add database constraints and lock or version checks where concurrent writes can conflict. Test contention against the database used in deployment.

Use the API starter's `Idempotency-Key` support for retryable JSON writes. A database transaction does not atomically commit a remote payment, email, or message broker. Use a durable outbox, provider idempotency keys, reconciliation, and compensating operations for those boundaries.

## Treat money and audit data deliberately

For financial applications, store amounts as validated integer minor units or database decimal values with explicit currency and rounding rules. Never use floating point for authoritative balances. Use an application-specific double-entry ledger, immutable journal records, reconciliation, least-privilege database access, and independently reviewed business controls.

The event replay table, runtime logs, and queue records are operational tools. They are not a financial ledger, tamper-evident audit system, compliance certification, or a replacement for independent security review. This development preview has no banking certification or independent security audit.

## Keep interactive work short

Return bounded, paginated API responses. Queue reports, imports, notifications, and bulk tasks. Make every job safe to repeat. Use SSE for one-way browser updates and WebSockets when the client must send commands over a persistent connection. Authorize every channel and command, and refresh resource snapshots after a replay gap.

The current real-time transport reads a SQL event history. Test its polling demand; do not infer unlimited connection capacity from a connection limit. For a deployment that needs very large fan-out, evaluate a dedicated broker and transport adapter before launch.

## Operate multiple instances

Use a shared production database, central secrets, synchronized clocks, and HTTPS. Configure shared Redis through REDIS_URL for cache, sessions, strict rate limits, and distributed locks. Without Redis these services use single-host filesystem storage. Run the SQL-outbox relay for committed Redis notifications, and monitor session, limiter, lock, and relay failures.

Run queue workers and WebSocket workers under a supervisor with an unprivileged user. Restart workers during deployment so code and registrations stay current. Deploy migrations compatibly with both the old and new application versions. Back up private data and verify restoration; keep application keys available to decrypt retained data.

## Define and measure a workload

Write down expected request rates, concurrent socket connections, database size, job latency, and acceptable failure rates. Measure p50/p95/p99 latency, throughput, memory, database locks, worker saturation, and recovery after a dependency failure. Include realistic authorization, data, and writes in the test.

A microbenchmark of a hello route cannot establish that one framework is better for every project. The repository's narrow benchmark is a reproducible development measurement, not a production capacity promise. Before selecting Elang for a critical system, validate your own workload and perform independent code and security review.

Start with [API and frontend integration](/docs/v0.1/frontend), [queues](/docs/v0.1/queues), [real-time transports](/docs/v0.1/realtime), and [deployment](/docs/v0.1/deployment).
