# Маршрутизация

Маршруты связывают HTTP-методы и пути с обработчиками. Определяйте их в `routes/web.php` с помощью маршрутизатора, предоставленного файлом начальной загрузки.

## Методы и параметры

```php
$router->get('/posts/{id}', fn (int $id) => ['id' => $id], 'posts.show')
    ->where('id', '[0-9]+');
$router->post('/posts', [PostController::class, 'store'], 'posts.store');
$router->put('/posts/{id}', [PostController::class, 'update']);
$router->patch('/posts/{id}', [PostController::class, 'patch']);
$router->delete('/posts/{id}', [PostController::class, 'destroy']);
```

Маршруты GET также принимают HEAD. Недопустимые целочисленные параметры приводят к ответу 400. Неизвестные пути возвращают 404, а неподдерживаемые методы — 405 с заголовком Allow.

## Именованные URL

```php
$url = $router->url('posts.show', ['id' => 42]);
```

Имена должны быть уникальными. В Twig используйте `{{ route('posts.show', {id: 42}) }}`.

## Группы и middleware

```php
use Elang\Http\Middleware\RateLimitMiddleware;

$router->group('/api', [RateLimitMiddleware::class], function ($router) {
    $router->get('/status', fn () => ['status' => 'ok']);
});
```

Группы могут быть вложенными. Middleware маршрута выполняются в порядке регистрации, оборачивая обработчик. Добавляйте дополнительные middleware с помощью `->middleware(MyMiddleware::class)`.

## Просмотр маршрутов

```bash
php elang routes
```
