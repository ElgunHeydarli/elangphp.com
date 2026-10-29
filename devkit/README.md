# Local development stack

Use this only in a development clone, never in the production checkout. Docker Engine/Compose v2 must be installed. The example credentials are intentionally local-only; services have no database/Redis/S3 API ports published to the host. Web and Mailpit bind loopback.

```bash
docker compose up --build app
docker compose --profile workers up queue outbox
```

Open http://localhost:8000, Mailpit http://localhost:8025. The app uses PostgreSQL, Redis, private SeaweedFS S3-compatible storage, captured SMTP, and a local OpenTelemetry collector. A persistent private runtime volume holds the development key. The entrypoint does not overwrite the checkout's .env. The root source checkout runs the official website; a generated SaaS project can copy compose.yaml and devkit/ to run the reference application instead.

Run `docker compose exec app php elang doctor` and the test command appropriate to the clone. Workers require completed setup; wait for the app startup migration output before starting the worker profile. Sources are mounted for local development. A production image must copy reviewed immutable source, install locked production dependencies, run as an unprivileged user, and use real secret management and a supervised web runtime.

`docker compose down` preserves volumes. `docker compose down --volumes` destroys development SQL, objects, Redis persistence, and the development encryption key; use it only for an intentional disposable reset. Do not run that against a stack containing data you need.

Image tags are explicit examples to review/update with dependency policy. Validate and boot the stack in your Docker environment; local source-level tests alone do not prove Docker image availability or platform compatibility. The collector prints trace summaries locally and is not a production tracing backend.

The SeaweedFS image is digest-pinned to the reviewed [4.51 release](https://github.com/seaweedfs/seaweedfs/releases/tag/4.51). Its S3 credential file intentionally contains development-only demo credentials. No anonymous identity is configured. The application creates its development bucket through signed SDK requests. Do not reuse these credentials outside this private local stack.
