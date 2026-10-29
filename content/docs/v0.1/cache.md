# Cache

The container binds PSR-16 CacheInterface to a filesystem-backed Symfony cache. Inject the interface rather than a concrete implementation.

## Store and retrieve

```php
use Psr\SimpleCache\CacheInterface;

$cache->set('homepage.summary', ['posts' => 12], 300);
$summary = $cache->get('homepage.summary', []);
$cache->delete('homepage.summary');
```

TTL values are seconds, DateInterval objects, or null. Use application-controlled keys; PSR-16 reserves some characters.

## In a handler

```php
$router->get('/summary', function (CacheInterface $cache) {
    return $cache->get('homepage.summary', ['posts' => 0]);
});
```

## Clear application cache

```bash
php elang cache:clear
```

The command clears the application cache pool. Twig's compiled templates are separate and automatically reload when source files change. Multi-server applications should bind a suitable shared cache adapter.
