# Redis and distributed infrastructure

Elang can share cache, sessions, rate limits, and locks through Redis. SQL remains the durable authority for jobs and real-time replay. Redis Streams carry committed notifications that wake workers and socket processes. This arrangement keeps application writes, queue dispatch, and event publication inside one SQL transaction.

## Connect an application

Install the full framework, create runtime tables, and configure one Redis endpoint shared by all application processes:

```dotenv
REDIS_URL=redis://127.0.0.1:6379/0
```

```bash
php elang platform:setup
php elang outbox:work
php elang queue:work
```

Put Redis on a private network, require authentication/ACLs, and use TLS where traffic crosses trust boundaries. Keep the DSN in a secret manager or untracked `.env`; never put credentials in commands, logs, screenshots, or Git. Restrict keys and commands to the application's needs. A Redis database number does not isolate Pub/Sub, so Elang also prefixes every application channel and key namespace.

The default prefix derives from `APP_URL`. Applications sharing the same URL should explicitly set a unique deployment prefix in configuration:

```php
'redis' => [
    'url' => Elang\Support\Config::env('REDIS_URL', ''),
    'prefix' => 'acme:production',
],
```

Use a different prefix for staging. Prefix changes sign users out of Redis-backed sessions and start fresh limiter/cache namespaces. Do not run a cache-clearing command against an unrelated application's namespace.

## Cache and locks

```php
$cache = $app->container->get(Psr\SimpleCache\CacheInterface::class);
$cache->set('catalog.version', 4, 300);

$runtime = $app->container->get(Elang\Infrastructure\Runtime::class);
$lock = $runtime->lock('report:42', 30);
if ($lock->acquire()) {
    try {
        // Perform bounded, idempotent work. Refresh before the lease expires.
    } finally {
        $lock->release();
    }
}
```

Without `REDIS_URL`, cache and locks use the private local filesystem. This fallback is useful on one host. It does not coordinate multiple hosts. Use `Runtime::lock()` for application-prefixed resources; a directly injected Symfony `LockFactory` requires you to choose properly namespaced resource names.

Distributed session reads take a per-session lock. Session data expires after two hours. The lock has a 30-second lease; stale writers must not overwrite a newer session. Keep session-holding requests short and release the session before long SSE responses or external calls. Authentication revocation is still checked against SQL on subsequent requests.

## Durable SQL outbox

When Redis is enabled, `Queue::push()` and `Events::publish()` append an outbox row on the same SQL connection as their authoritative write. Rollback removes all related writes. The relay reads only committed rows, publishes to a bounded Redis Stream, and marks rows delivered after Redis accepts them. A deduplication key protects normal relay retries for seven days. Consumers still need idempotent processing: this is at-least-once delivery, not an exactly-once promise.

```bash
php elang outbox:dispatch
php elang outbox:work
php elang operations:prune
```

Supervise the continuous relay separately from queue workers. A queue worker can also relay notifications before waiting, but a separate relay keeps event wakeups timely when the queue is continuously busy. Do not relay from inside an open application transaction. Do not delete unpublished outbox rows to clear a backlog.

Redis loss does not delete SQL jobs or replay history. Restore Redis, restart the relay, and monitor the pending outbox count. Cache/session/limiter availability has its own impact: application cache reads may degrade to cache misses, while session, limiter, and lock operations report Redis failures. They do not silently switch to independent local authorization state. Applications should return a controlled service error and alert an operator.

## Real-time ordering and recovery

Event IDs are monotonically increasing within a channel. A SQL row lock serializes channel sequence allocation through the outer business transaction. This avoids treating an auto-increment allocation order as a commit order. Rollbacks do not publish phantom events. Sequence numbers are not globally comparable across channels.

Socket processes read bounded Redis notification batches and group clients by channel/cursor, then query SQL once for each selected group. A bounded SQL reconciliation sweep also runs so missed Redis notices can recover. The implementation uses synchronous bounded SQL/Redis calls; it is not a measured nonblocking capacity guarantee. Isolate WebSocket workers, tune database indexes and pools, and run your own realistic load test.

Keep replay retention compatible with expected offline time. A client reconnecting beyond retained history must refresh its application snapshot. Short-lived socket capabilities do not replace current business authorization for new tenant writes. See [real-time](/docs/v0.1/realtime), [queues](/docs/v0.1/queues), and [deployment](/docs/v0.1/deployment).
