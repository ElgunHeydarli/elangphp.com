# Testing an application

The TestClient runs requests through the real kernel and middleware, with a separate in-memory session per client. It refuses applications whose environment is not explicitly testing. Create an isolated database and storage directory; the client does not clone or replace your database for you.

## Working example
Install `phpunit/phpunit:^12` as a development dependency. Save the complete example below as `tests/ApiTest.php`; run `vendor/bin/phpunit --bootstrap vendor/autoload.php tests`. In your application test factory, register your real routes and service providers on the isolated application so tests exercise your application behavior.

```bash
composer require --dev phpunit/phpunit:^12
```



```php
use Elang\Application;
use Elang\Testing\TestClient;
use PHPUnit\Framework\TestCase;

final class ApiTest extends TestCase
{
    public function testReadyEndpoint(): void
    {
        $directory = sys_get_temp_dir().'/my-elang-test-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700, true);
        try {
            $app = new Application($directory, [
                'environment' => 'testing',
                'database' => ['url' => 'sqlite:///:memory:'],
                'mail' => ['dsn' => 'null://null'],
                'redis' => ['url' => ''],
                'telemetry' => ['enabled' => false],
                'operations' => ['enabled' => false],
            ]);
            $app->router->get('/api/ready', fn () => ['ready' => true]);
            $browser = new TestClient($app);
            $browser->get('/api/ready')->assertOk()->assertJsonPath('ready', true);
        } finally {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                $file->isDir() && !$file->isLink()
                    ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($directory);
        }
    }
}
```

Assertions include status, headers, redirects, visible text, JSON paths/counts and bound-value database checks. Use `withSession()` for test session values and `actingAs($persistedUser)` for authenticated requests. Session clients remain separate even when sharing an application.

CSRF is active. A protected write without a token should return 419. For a valid session write use `$browser->withCsrf()->post(...)`; this supplies the real CSRF header and same-origin information instead of disabling middleware. Scope checks and record ownership still apply.

The client replaces the injected Elang HTTP client with a strict FakeTransport and Symfony MailerInterface with an in-memory recorder. Configure `$browser->http->when(...)`, assert HTTP requests, and inspect `$browser->mail->messages()` or assertSentCount/assertSentTo. Fakes are shared between clients using the same application. Direct cURL calls, independently constructed mail transports and unrelated SDKs are outside this interception; replace those adapters explicitly. Never point application tests at a live database.

## Try it together

Run the complete catalogue example from an application with the optional ORM installed. It uses a disposable in-memory database and a fake partner API. Expected output includes `"checks":"passed"`. The example covers entity relationships, fetch joins, resources, collections and HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Next step](/docs/v0.1/deployment) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Common mistakes

| Symptom | Fix |
| --- | --- |
| Protected write returns 419 | Assert the rejection or use withCsrf() for a valid same-origin session write. |
| Another SDK still contacts the network | Replace that adapter explicitly; only the injected Elang client and mailer are faked. |
