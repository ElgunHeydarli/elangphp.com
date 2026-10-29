# JSON API qurun

Cavabınızı kontrollerə köçürün və məhdudiyyətli identifikator əlavə edin.

## Kontroller yaradın

```bash
php elang make:controller PostController
```

Kontrolleri redaktə edin:

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

## Marşrut qeydiyyata alın

```php
use App\Controllers\PostController;
$router->get('/api/posts/{id}', [PostController::class, 'show'], 'posts.show')
    ->where('id', '[0-9]+');
```

## Nəticəni yoxlayın

`/api/posts/42` açın. JSON-da tam ədəd tipli 42 ID-si olmalıdır. `/api/posts/nope` marşrut tələbinə uyğun gəlmədiyi üçün 404 qaytarmalıdır.

## Növbəti addım

Kodda sabit yazılmış məlumatlar sorğunun həyat dövrünü anlamaq üçün faydalıdır. Verilənlər bazası dərsi həmin məlumatları SQLite qeydləri ilə əvəz edir.
