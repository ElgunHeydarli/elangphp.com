# Service container

The Elang container implements PSR-11. It resolves concrete classes through constructor injection and supports explicit bindings for interfaces and scalar configuration.

## Bind an interface

```php
$app->container->bind(Clock::class, SystemClock::class);
```

A binding creates a fresh service when resolved. Use a closure when the service needs configuration.

## Shared services

```php
use Elang\Container\Container;

$app->container->singleton(Client::class, fn (Container $container) =>
    new Client('https://api.example.com')
);
```

`singleton()` reuses one instance. `instance()` registers an existing value. Unbound concrete classes are constructed afresh. Circular dependencies raise a container exception with the resolution chain.

## Dependency injection

```php
final class ReportController
{
    public function __construct(private ReportService $reports) {}
}
```

Scalar arguments need an explicit factory or default value. Union types are not automatically resolved. Named route values take precedence over injectable handler arguments.

## Service providers

Implement `Elang\Contracts\ServiceProvider` with `register(Container $container)` and `boot(Application $app)`, then call `$app->provider(new MyProvider())`. Register dependencies before adding routes or listeners that use them.

Request-aware services should remain transient. Elang currently targets ordinary PHP request lifecycles; long-running HTTP worker integration is not included.
