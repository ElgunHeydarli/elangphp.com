# Elang, Laravel, CodeIgniter and Symfony: choosing by evidence

This comparison describes the development preview available on 30 October 2026. Elang is an independent PHP framework using Symfony components and Doctrine; it is not a Laravel distribution and does not implement Laravel’s API.

Laravel documents Eloquent models, relationships and collections as part of its application development approach. CodeIgniter documents Model CRUD, validation, pagination, timestamps and soft deletion. Symfony provides reusable components including HttpFoundation, Console, Cache and Clock. Those projects have their own conventions and ecosystems; a similar feature name does not imply source compatibility.

[Laravel Eloquent](https://laravel.com/framework/docs/13.x/eloquent), [CodeIgniter Model](https://codeigniter.com/user_guide/models/model.html), [Symfony components](https://symfony.com/doc/current/components/index.html), [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/getting-started.html).

## Elang capabilities available now

| Area | Implemented behavior | Learn it |
| --- | --- | --- |
| HTTP and services | Routing, middleware, PSR-11 injection, Symfony validation | [routing](/docs/v0.1/routing) |
| Accounts | Verified accounts, roles/policies, scoped tokens, MFA/passkeys | [account-security](/docs/v0.1/account-security) |
| Database | DBAL repositories plus optional Doctrine ORM | [orm](/docs/v0.1/orm) |
| Data transformations | Immutable collections and lazy streams | [collections](/docs/v0.1/collections) |
| JSON output | Explicit public fields and core pagination metadata | [api-resources](/docs/v0.1/api-resources) |
| Partner integrations | TLS client, bounded responses, scoped tokens, strict fakes | [http-client](/docs/v0.1/http-client) |
| Testing | Kernel requests, sessions, CSRF, in-memory mail and HTTP assertions | [application-testing](/docs/v0.1/application-testing) |
| Application commands | Symfony Console extensions and non-overriding registration | [custom-console](/docs/v0.1/custom-console) |
| Background and live updates | Database jobs/schedules, SSE, authenticated WebSockets | [queues](/docs/v0.1/queues) |
| Application foundations | Redis, teams, private storage, notifications, telemetry, starters | [fullstack-starters](/docs/v0.1/fullstack-starters) |

## Choose a data approach

Keep the explicit DBAL repositories for small, direct data operations. Opt in to Doctrine ORM when relationships, a unit of work and an identity map suit the domain. The two share a DBAL connection, but core Model objects and Doctrine entities are separate models; do not assume their factories or pagination adapters are interchangeable.

## What remains work

Additional queue broker adapters, semantic documentation search, dedicated editor integrations and automated upgrade tooling remain planned. A stable release also needs API stabilization, a support policy, independent security review and broader application/workload evidence. Billing providers, search engines, OAuth vendors and other ecosystem integrations must be evaluated individually; Elang does not claim every third-party package from another framework.

## Evaluate a real project

Implement the same feature, inputs, authorization rules, data operations and tests in each candidate. Record setup time, maintenance work and failures. A performance comparison must state PHP/SAPI, OPcache, hardware, dependencies, payload, warm-up and concurrency, with reproducible raw results. No universal speed, security or production-capacity superiority is established here.

Start with the task-board lesson, then the partner API, entities/resources and application-testing lessons. All three languages use the same executable code. The catalogue example is checked by the suite against a disposable database and fake partner API.

[Learn](/learn) · [ORM](/docs/v0.1/orm) · [Application testing](/docs/v0.1/application-testing)
