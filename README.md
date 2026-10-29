# Elang

**Build with clarity.** An approachable PHP framework with an explicit application API, modular services, and established open-source building blocks.

**Status: 0.1 development preview.** APIs can change. This is not a stable production release, and no comparative performance claim is made. The source is intended for evaluation and feedback before a stable release.

The official website is an Elang application: the backend uses this framework, while the frontend is HTML, CSS, and vanilla JavaScript. No Node.js build is needed to run it.

## What works today

- HTTP kernel, named routing, constrained parameters, groups, and middleware.
- PSR-11 service container, autowiring, explicit factories, and service providers.
- Symfony HttpFoundation requests/responses; Twig HTML templates with autoescaping.
- Symfony constraint validation and structured 422 responses.
- Doctrine DBAL, bound query parameters, transactions, and migration commands.
- Session and CSRF middleware, Argon2id passwords, Sodium secretbox encryption.
- Locked filesystem-backed API rate limits, safe error responses, request IDs, and default security headers.
- PSR-16 cache, PSR-3 logs, synchronous events, and a configurable mail transport.
- A standalone application generator and controller/migration generators.
- English, Azerbaijani, and Russian documentation, ten lessons, code examples, search, Atom, a sitemap, Markdown exports, and llms.txt.

- Account authentication, verified email, recovery, forced temporary-password rotation, roles, policies, and scoped API tokens.
- Explicit model repositories, relations, pagination, factories, and seeders.
- Private Content Studio with drafts, publishing, locale-specific content, and revision history.
- Local-only Studio for request traces, SQL templates/timing, routes, stack frames, and encrypted mailbox.
- Database JSON jobs, leases, retries, failed jobs, and deduplicated UTC scheduling.
- Private SSE/WebSocket channels, encrypted short-lived capabilities, registered commands, and ordered event replay.
- CORS, OpenAPI, a fetch ES module, React/Next.js examples, and idempotency for buffered JSON writes.
- A read-only documentation MCP endpoint over HTTP or stdio.

A full ORM, distributed cache/limiter/broker adapters, and dedicated editor plugins are planned. See [ROADMAP.md](ROADMAP.md).

## Requirements

PHP 8.3+ within PHP 8.x, Composer 2, and the extensions in composer.json. SQLite examples need pdo_sqlite; MySQL needs pdo_mysql and PostgreSQL needs pdo_pgsql. Password hashing requires a PHP build with Argon2id support.

## Run this website locally

```bash
git clone https://github.com/ElgunHeydarli/elangphp.com.git elang
cd elang
composer install
cp .env.example .env
php elang key:generate
php elang migrate
php elang serve
```

Visit http://127.0.0.1:8000. No JavaScript dependency installation or frontend compilation is required.

## Create an application from your terminal

```bash
composer create-project --repository=https://elangphp.com/packages elang/elang my-app 0.1.0-dev
cd my-app
php elang serve
```

Composer installs dependencies, generates the key, and runs doctor. The preview uses Elang's own HTTPS Composer repository; a Packagist release is not published yet.

For a reusable CLI:

```bash
composer global config repositories.elang composer https://elangphp.com/packages
composer global require elang/installer:0.1.0-dev
```

Add the directory printed by `composer global config bin-dir --absolute` to your user PATH before using the global command. Then run `elang new my-app`.

Application starters: `elang new my-blog --starter=blog`, `elang new my-api --starter=api`, `elang new my-workspace --starter=dashboard`, and `elang new my-auth --starter=auth`. Composer packages are `elang/blog`, `elang/api`, `elang/dashboard`, and `elang/auth-starter`.

For WebSockets in a generated project: `composer require elang/realtime:0.1.0-dev`, then `php elang realtime:serve`. Keep the daemon behind a TLS reverse proxy in production. The default SQL event store polls; load-test its database demand for your workload.

Create the complete learning example with `elang new task-board --example=task-board`, or use Composer create-project with package `elang/task-board` and the same repository and version.

Framework contributors can still use `php elang new ../my-app --local` to test a sanitized source snapshot.

## Build the Composer repository

Run `php bin/build-packages` before deploying. It builds nine Composer packages, validates distribution boundaries, and writes content-addressed archives plus packages.json into private storage. The site exposes only the catalog and known archive names through controlled routes. No manual archive download is part of the public installation flow.

The package builder needs PHP zip. The installer is implemented in packages/installer; it creates projects through Composer. The public installation URL requires the domain's DNS and HTTPS configuration to be working.

## A first route

```php
use Elang\Http\Response;

$router->get('/hello/{name}', function (string $name) {
    return Response::json(['message' => 'Hello, '.$name.'!']);
}, 'hello');
```

## Verification

```bash
composer validate --strict
composer test
composer analyse
composer audit
```

GitHub Actions runs these checks on PHP 8.3 and 8.4. Tests cover the container, HTTP behavior, middleware, CSRF, encryption, validation, caching, events, rate limiting, database transactions/migrations, all documentation pages, search, and generators. SQLite runs in the regular suite. Optional disposable-database integration tests cover MySQL/PostgreSQL through ELANG_TEST_DATABASE_URL; the CI database job provisions both services.

## Project layout

```text
src/                   Elang framework (PSR-4: Elang)
app/                   Documentation-site backend
bootstrap/             Site application setup
config/                Resolved configuration
routes/                Route definitions
public/                Public entry point, HTML-facing assets
resources/views/       Server-rendered Twig HTML
content/               Versioned Markdown docs, lessons, examples
database/migrations/   Site demonstration schema
storage/               Private runtime cache, logs, sessions, locks
tests/                 PHPUnit suite
```

As a dependency, the package autoloads only Elang classes. The website registers its own App namespace in bootstrap. A generated application owns its own App namespace.

## Deployment

Expose only `public/`, keep `.env` private, configure HTTPS, and give the PHP process write access to storage and the database location. Set APP_ENV=production, APP_DEBUG=false, and the canonical APP_URL. Review migrations before `php elang migrate --force`.

On the current Hestia host, application source lives in `/home/ecommerce/web/elangphp.com/private/elang`; `bin/publish-site` copies only public assets and the front controller to the fixed public_html root. It preserves the previous placeholder in the private directory. This script is specific to this host layout.

The mailer defaults to `null://null`. Account email is encrypted in the local Studio mailbox; production account recovery reports unavailable until a real MAILER_DSN and MAIL_FROM are configured. The rate limiter uses one host's filesystem and locks; multi-host deployments require shared adapters.

## Documentation and AI tools

Read the versioned documentation at `/docs/v0.1/introduction`. Append `.md` for a clean Markdown representation. `/llms.txt` indexes the documentation. [AGENTS.md](AGENTS.md) documents repository conventions. These files help tools retrieve context; they do not guarantee model training or search visibility.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md), [SECURITY.md](SECURITY.md), and [LICENSE](LICENSE).
