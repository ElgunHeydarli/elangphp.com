# Routing

Routes connect HTTP methods and paths to handlers. Define them in `routes/web.php` using the router supplied by your bootstrap file.

## Methods and parameters

```php
$router->get('/posts/{id}', fn (int $id) => ['id' => $id], 'posts.show')
    ->where('id', '[0-9]+');
$router->post('/posts', [PostController::class, 'store'], 'posts.store');
$router->put('/posts/{id}', [PostController::class, 'update']);
$router->patch('/posts/{id}', [PostController::class, 'patch']);
$router->delete('/posts/{id}', [PostController::class, 'destroy']);
```

GET routes also accept HEAD. Invalid integer parameters produce a 400 response. Unknown paths return 404, while unsupported methods return 405 with an Allow header.

## Named URLs

```php
$url = $router->url('posts.show', ['id' => 42]);
```

Names must be unique. In Twig, use `{{ route('posts.show', {id: 42}) }}`.

## Groups and middleware

```php
use Elang\Http\Middleware\RateLimitMiddleware;

$router->group('/api', [RateLimitMiddleware::class], function ($router) {
    $router->get('/status', fn () => ['status' => 'ok']);
});
```

Groups can be nested. Route middleware runs in registration order, around the handler. Attach extra middleware with `->middleware(MyMiddleware::class)`.

## Inspect routes

```bash
php elang routes
```
