# Get ready to deploy

A working local application needs explicit configuration before it goes online.

## Review the application

Check your validation, authorization decisions, database queries, and error behavior. Run your test suite. The framework's tests do not replace application-specific tests.

## Prepare dependencies

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```

Set `APP_ENV=production`, `APP_DEBUG=false`, the canonical APP_URL, and an application key. Keep secrets and writable files private.

## Set the web root

Expose only `public/`. Configure PHP-FPM and route nonexistent paths to `index.php`. The deployment guide includes Apache and Nginx guidance.

## Apply schema changes

Review pending migrations and back up important data before running `php elang migrate --force`. Verify representative pages, JSON errors, and logs after deployment.

## Know the release status

Elang 0.1 is a development preview with changing APIs. Use it for evaluation and controlled projects while compatibility and security testing continue.
