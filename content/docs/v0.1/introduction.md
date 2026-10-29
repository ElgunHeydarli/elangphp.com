# Meet Elang

Elang is a PHP framework built around an explicit application API and modular services. Write routes, compose services, return responses, and keep your application understandable.

This website runs on Elang and offers English, Azerbaijani, and Russian content. Its frontend is HTML, CSS, and vanilla JavaScript; its backend uses the same router, container, and services documented here.

## Current status

**0.1 development preview.** This is an early working foundation, not a stable production release. Public APIs may change. There is no claim of outperforming Laravel and only a narrow development-server comparison; no general performance superiority is established.

## What is included

- An HTTP kernel, named routes, route groups, and middleware.
- A PSR-11 container with constructor injection and explicit bindings.
- Twig templates with HTML escaping enabled.
- Symfony validation constraints with structured errors.
- Doctrine DBAL connections, a query builder, and migration commands.
- Sessions, CSRF middleware, Argon2id password hashing, and Sodium encryption.
- PSR-16 caching, PSR-3 logging, events, mail transport, and a console.

## What is not included yet

Authentication, roles, scoped API tokens, a model/repository layer, local Studio, and the read-only documentation MCP server are included in this preview. Background jobs, UTC scheduling, SSE, authenticated WebSockets, frontend CORS, and idempotency for JSON writes are included. Redis-backed infrastructure, transactional outbox notifications, MFA/passkeys/device management, organizations, private files, inbox notifications, OpenTelemetry, and React/Next/SaaS starters are implemented. A full ORM, additional broker integrations, and automated upgrade tooling remain future work.

## Requirements

PHP 8.3 or later within PHP 8.x, Composer 2, and the extensions declared in `composer.json`. SQLite examples also need `pdo_sqlite`. MySQL needs `pdo_mysql`; PostgreSQL needs `pdo_pgsql`.

Continue with [installation](/docs/v0.1/installation), or follow the [learning path](/learn).
