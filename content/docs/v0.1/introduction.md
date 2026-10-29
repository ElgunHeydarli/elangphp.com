# Meet Elang

Elang is a PHP framework built around a small, explicit application API. Write routes, compose services, return responses, and keep your application understandable.

This website runs on Elang. Its frontend is HTML, CSS, and vanilla JavaScript; its backend uses the same router, container, and services documented here.

## Current status

**0.1 development preview.** This is an early working foundation, not a stable production release. Public APIs may change. There is no claim of outperforming Laravel and no published comparative benchmark yet.

## What is included

- An HTTP kernel, named routes, route groups, and middleware.
- A PSR-11 container with constructor injection and explicit bindings.
- Twig templates with HTML escaping enabled.
- Symfony validation constraints with structured errors.
- Doctrine DBAL connections, a query builder, and migration commands.
- Sessions, CSRF middleware, Argon2id password hashing, and Sodium encryption.
- PSR-16 caching, PSR-3 logging, events, mail transport, and a console.

## What is not included yet

An ORM, complete login and permissions system, queue worker, scheduler, and automated upgrade tool are future work. Password hashing and session primitives alone do not constitute an authentication system.

## Requirements

PHP 8.3 or later within PHP 8.x, Composer 2, and the extensions declared in `composer.json`. SQLite examples also need `pdo_sqlite`. MySQL needs `pdo_mysql`; PostgreSQL needs `pdo_pgsql`.

Continue with [installation](/docs/v0.1/installation), or follow the [learning path](/learn).
