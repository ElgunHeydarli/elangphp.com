# Marşrutlaşdırma

Marşrutlar HTTP metodlarını və yollarını emaledicilərə bağlayır. Onları başlanğıc faylınızın verdiyi marşrutlaşdırıcıdan istifadə edərək `routes/web.php` faylında təyin edin.

## Metodlar və parametrlər

```php
$router->get('/posts/{id}', fn (int $id) => ['id' => $id], 'posts.show')
    ->where('id', '[0-9]+');
$router->post('/posts', [PostController::class, 'store'], 'posts.store');
$router->put('/posts/{id}', [PostController::class, 'update']);
$router->patch('/posts/{id}', [PostController::class, 'patch']);
$router->delete('/posts/{id}', [PostController::class, 'destroy']);
```

GET marşrutları HEAD metodunu da qəbul edir. Yanlış tam ədəd parametrləri 400 cavabı yaradır. Naməlum yollar 404, dəstəklənməyən metodlar isə Allow başlığı ilə 405 qaytarır.

## Adlandırılmış URL-lər

```php
$url = $router->url('posts.show', ['id' => 42]);
```

Adlar unikal olmalıdır. Twig-də `{{ route('posts.show', {id: 42}) }}` istifadə edin.

## Qruplar və aralıq proqram təminatı

```php
use Elang\Http\Middleware\RateLimitMiddleware;

$router->group('/api', [RateLimitMiddleware::class], function ($router) {
    $router->get('/status', fn () => ['status' => 'ok']);
});
```

Qruplar iç-içə yerləşə bilər. Marşrutun aralıq proqram təminatı qeydiyyat ardıcıllığı ilə emaledicinin ətrafında işləyir. Əlavə aralıq proqram təminatını `->middleware(MyMiddleware::class)` ilə qoşun.

## Marşrutları yoxlayın

```bash
php elang routes
```
