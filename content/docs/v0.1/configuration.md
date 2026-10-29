# Configuration

Configuration lives in `config/app.php`. It returns a PHP array; environment-specific values live in an untracked `.env` file.

## Environment values

```dotenv
APP_ENV=local
APP_DEBUG=false
APP_URL=http://localhost:8000
APP_KEY=
```

Generate an encryption key with `php elang key:generate`. The command stores it in `.env` without printing it. Replacing a key makes previously encrypted data unreadable.

## Read configuration

```php
use Elang\Support\Config;

$router->get('/application', fn (Config $config) => [
    'name' => $config->get('name', 'My application'),
]);
```

Dot-separated keys traverse nested arrays. `Config::env()` is intended for configuration files, so the rest of your application can depend on resolved configuration.

## Production defaults

Set `APP_ENV=production`, `APP_DEBUG=false`, and the canonical HTTPS `APP_URL`. Keep credentials outside the public directory. The default mail transport discards messages until you configure `MAILER_DSN`.

## Request limits

The site sets `max_body_bytes` to 16 KiB and `rate_limit` to 120 API requests per minute per detected client IP. Set appropriate limits for your own application. Reverse-proxy trust must be configured explicitly when needed; arbitrary forwarded headers are not trusted by default.
