# Operations and OpenTelemetry

Elang provides private operational summaries and optional manual OpenTelemetry traces. The operations screen shows queue state, failed job metadata, SQL-outbox backlog, worker heartbeats, and one-hour HTTP aggregates. It does not expose job bodies, cookies, bearer tokens, SQL values, or user emails.

## Prepare and enable operations

```bash
php elang platform:setup
```

```dotenv
OPERATIONS_ENABLED=true
```

```php
$app->provider(new Elang\Observability\Provider());
```

Open `/operations` as a verified administrator with a rotated permanent password. `/operations/metrics` returns the same private JSON summary under the same account/role checks. Failed-job retries are POST forms protected by CSRF. Roles supplied by a browser never grant access. Do not make this endpoint public or treat its aggregate JSON as a public health check.

HTTP metrics use route names rather than raw URL paths/query strings. They store request count, server-error count, summed duration, and maximum duration in minute buckets. The screen calculates mean latency. These values are not percentiles and should not be presented as p95/p99. If monitoring storage fails, request handling continues; alert separately on missing telemetry.

Queue/outbox workers report a random process identifier, kind, last heartbeat, and attempts processed. A heartbeat older than 120 seconds is marked stale. A long-running job may legitimately exceed this interval, so use workload-specific heartbeat/lease policies. An empty worker list is not proof that a process is stopped; verify the process supervisor and monitoring configuration.

```bash
php elang queue:work --max-jobs=1000
php elang outbox:work
php elang operations:prune
```

Run pruning from a supervised scheduler. It keeps HTTP aggregates for 24 hours, worker metadata for seven days, and published outbox rows for seven days. Unpublished rows are retained. Business audit records and failed jobs have separate retention decisions.

## Enable traces

```dotenv
OTEL_ENABLED=true
OTEL_SERVICE_NAME=orders-api
OTEL_EXPORTER_OTLP_TRACES_ENDPOINT=http://127.0.0.1:4318/v1/traces
OTEL_SAMPLE_RATE=0.1
```

The framework uses the maintained OpenTelemetry PHP SDK and OTLP HTTP/protobuf exporter. No native OpenTelemetry extension is required for these manual spans. A local Collector is the recommended export boundary; use HTTPS for remote collection. Custom HTTP export is limited to loopback or the development collector hostname. Secure the collector, its downstream credentials, and access to your trace store.

HTTP server spans contain method, route name, and response status. Queue consumer spans contain the registered job name and propagate W3C trace context from dispatch. No request body/query, authorization header, raw path parameter, email, SQL parameter, or exception message is deliberately attached. Error classes and status are sufficient for this baseline. Review every custom span attribute before adding sensitive values or high-cardinality identifiers.

Root trace sampling defaults to 10%; valid parent trace context influences the parent-based sampler. Public clients can submit trace headers, so do not use them for authorization and consider discarding untrusted upstream context at your edge if required. Jobs persist only trace context, separate from business payloads.

Export uses a bounded 250 ms transport timeout with no export retries. Batch processing still may export during a request, flush, or shutdown; it is not a separate background thread and is not a zero-latency guarantee. Keep the collector nearby. Export failure must not expose secrets or break business behavior. Long-lived processes should flush at controlled lifecycle boundaries and recycle under a supervisor.

## Operational response

- Rising queued count: verify worker process, registrations, available database capacity, and reservation duration.
- Rising failed count: inspect exception classes and application-side idempotency before retrying.
- Rising outbox count: restore Redis/relay connectivity; preserve unpublished rows.
- Missing HTTP metrics: verify `OPERATIONS_ENABLED`, table setup, and application route traffic.
- Missing traces: verify endpoint, collector protocol, sampling, network policy, and exporter dependencies.
- Stale workers: check process exit/restart history and whether a job blocks longer than the expected heartbeat interval.

Backups, independent uptime checks, alert routing, disaster recovery, and database/Redis/S3 host metrics belong to your deployment. A healthy framework dashboard cannot prove that backups restore or that external providers are functioning. See [deployment](/docs/v0.1/deployment) and [testing](/docs/v0.1/testing).
