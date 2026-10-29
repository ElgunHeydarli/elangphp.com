# Build a JSON API

Move your response into a controller and add a constrained identifier.

## Generate the controller

```bash
php elang make:controller PostController
```

Edit the controller:

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

## Register a route

```php
use App\Controllers\PostController;
$router->get('/api/posts/{id}', [PostController::class, 'show'], 'posts.show')
    ->where('id', '[0-9]+');
```

## Verify the result

Open `/api/posts/42`. The JSON should contain an integer ID of 42. `/api/posts/nope` should return 404 because it fails the route requirement.

## Next step

Hardcoded data is useful for understanding the request lifecycle. The database lesson replaces that data with records from SQLite.
