# Repository guidance

Elang is a PHP framework, not a separate language. The current version is 0.1.0-dev. Do not claim production readiness, benchmark superiority, a stable release, or Packagist publication without evidence.

## Architecture

- src/ contains the Elang namespace. Avoid coupling framework classes to the documentation site.
- app/, routes/, resources/views/, and content/ implement the official website using Elang itself.
- Frontend: server-rendered HTML, CSS, and vanilla JavaScript. No SPA framework or Node build is required.
- HTTP: Symfony HttpFoundation. This is not a PSR-7/PSR-15 implementation.
- Container: PSR-11. Cache: PSR-16. Logs: PSR-3.
- Database: Doctrine DBAL, not an ORM. Bind values; never interpolate user SQL expressions.
- Validation: Symfony Constraint objects, not Laravel rule strings.
- Templates: Twig, autoescaped and strict. Markdown conversion strips raw HTML.
- Mail and events run synchronously. No queue, scheduler, or built-in login system exists yet.

## Checks

Run composer validate --strict, composer test, composer analyse, and composer audit. PHP 8.3 and 8.4 are covered by the CI matrix. Add meaningful tests for changed behavior. Update versioned English documentation with API changes.

## Secrets and deployment

Never commit or print .env, keys, private credentials, databases, runtime logs, or vendor/. Expose only public/. On this host, source is private/elang and only public files are copied to public_html. Preserve that separation. Do not modify unrelated domains or global server settings.
