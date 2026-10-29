# Создание JSON API

Перенесите ответ в контроллер и добавьте идентификатор с ограничением.

## Создание контроллера

```bash
php elang make:controller PostController
```

Измените контроллер:

```php
namespace App\Controllers;
use Elang\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
final class PostController
{
    public function show(int $id): JsonResponse
    {
        return Response::json(['data' => ['id' => $id, 'title' => 'A small beginning']]);
    }
}
```

## Регистрация маршрута

```php
use App\Controllers\PostController;
$router->get('/api/posts/{id}', [PostController::class, 'show'], 'posts.show')
    ->where('id', '[0-9]+');
```

## Проверка результата

Откройте `/api/posts/42`. JSON должен содержать целочисленный ID, равный 42. `/api/posts/nope` должен возвращать 404, поскольку не соответствует требованию маршрута.

## Следующий шаг

Жёстко заданные данные полезны для понимания жизненного цикла запроса. Урок по базе данных заменяет их записями из SQLite.
