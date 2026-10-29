# Testing

The framework repository includes PHPUnit tests for HTTP behavior, the container, middleware, validation, security, database operations, and documentation routes.

## Run the suite

```bash
composer test
composer analyse
composer audit
```

The framework requires development dependencies for tests and static analysis. Install production dependencies using `composer install --no-dev` only in a separate production artifact.

## Make an HTTP request in a test

```php
use Elang\Application;
use Symfony\Component\HttpFoundation\Request;

$app = new Application($projectPath, ['debug' => false]);
$app->router->get('/hello', fn () => ['message' => 'Hello']);
$response = $app->handle(Request::create('/hello'));

self::assertSame(200, $response->getStatusCode());
self::assertSame('Hello', json_decode($response->getContent(), true)['message']);
```

Use a temporary project directory for isolated storage. Test authorization, unsafe inputs, failure responses, and database behavior in your own applications.

## Compatibility

The CI workflow targets PHP 8.3 and 8.4. A passing suite verifies the behaviors covered by the tests; it is not a security audit or evidence of performance superiority over another framework.
