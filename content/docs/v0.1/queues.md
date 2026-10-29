# Background jobs and scheduling

Keep slow work outside HTTP requests. Elang provides a database-backed JSON job queue, delayed jobs, bounded retries, failed-job inspection, and UTC scheduling. The same database connection lets you commit an application write and its queued job together.

## Prepare the runtime tables

Auth, blog, dashboard, and API starters include the migration. In another application run:

```bash
php elang queue:setup
```

Review schema changes before using `--force` in production. SQLite is useful locally. Use a shared MySQL or PostgreSQL database for multiple application servers.

## Register a handler

Register handlers during application bootstrap or in a service provider. HTTP processes and workers must load the same registrations. Payloads contain JSON data; Elang never deserializes PHP objects or accepts a class name from a job payload.

```php
use Elang\Queue\Jobs;
use Psr\Log\LoggerInterface;

$app->container->get(Jobs::class)->register(
    'reports.generate',
    function (array $payload, LoggerInterface $logger): void {
        // Load the record by ID and authorize the business operation here.
        $logger->info('Report requested', ['report_id' => $payload['report_id']]);
    }
);
```

## Dispatch and work

```php
use Elang\Queue\Queue;

$id = $app->container->get(Queue::class)->push(
    'reports.generate',
    ['report_id' => 42],
    delay: 10
);
```

```bash
php elang queue:work
php elang queue:work --once
php elang queue:work --stop-when-empty --max-jobs=100
php elang queue:failed
php elang queue:retry JOB_ID
```

Jobs are claimed with a conditional database update and a reservation token. Delivery is **at least once**: a worker crash or expired lease can cause another attempt. Make handlers idempotent using application-level unique constraints and external provider idempotency keys.

The default lease is 120 seconds and the maximum attempt count is five. Configure `queue.lease_seconds` and `queue.max_attempts` in `config/app.php`. Keep handler duration below the lease, configure supervisor timeouts below it, and split long reports into bounded chunks. Elang does not interrupt a running PHP handler or renew its lease automatically. Expired reservations can be reclaimed; an old reservation cannot acknowledge a newer worker's job.

Retries use exponential delay capped at one hour. Failed-job listings expose the exception class and metadata, not payloads or exception messages. Payloads live in private database storage; send record IDs instead of secrets. A process supervisor should restart workers after deploys and unexpected exits. `--max-jobs` recycles successful attempts; a worker that repeatedly finds an empty queue remains running.

## Commit domain data and a job together

```php
$database->connection()->transactional(function ($connection) use ($queue): void {
    $connection->insert('reports', ['status' => 'pending']);
    $queue->push('reports.generate', ['report_id' => (int) $connection->lastInsertId()]);
});
```

Both operations must use the same injected `Database` connection. An exception rolls them back together. External HTTP calls, email delivery, and separate databases are outside that transaction.

## Schedule jobs

```php
use Elang\Queue\Schedule;

$schedule = $app->container->get(Schedule::class);
$schedule->everyMinute('reports.minute', 'reports.generate', ['report_id' => 42]);
$schedule->dailyAt('reports.daily', '02:00', 'reports.generate', ['report_id' => 43]);
```

Run `php elang schedule:run` once each minute through cron. Times are UTC. A shared unique database run key prevents two scheduler processes from enqueueing the same schedule ID in the same minute. This does not prevent a long job from overlapping a later scheduled run. Add an application lock when overlap matters. Missed minutes are not backfilled.

Continue with [real-time events](/docs/v0.1/realtime) and [large application architecture](/guides/large-applications).
