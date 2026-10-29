# Kontrollerlər və cavablar

Kontrollerlər marşrut fayllarını əsas vəzifələrinə yönəlmiş saxlayır. Elang konstruktor asılılıqlarını və emaledici parametrlərini xidmət konteynerindən həll edir.

## Çağırıla bilən kontroller

```php
namespace App\Controllers;

use Elang\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HelloController
{
    public function __invoke(): JsonResponse
    {
        return Response::json(['message' => 'Hello from Elang']);
    }
}
```

Onu `$router->get('/hello', HelloController::class)` ilə qeydiyyata alın.

## Sorğunu oxuyun

```php
use Symfony\Component\HttpFoundation\Request;

$router->get('/search', function (Request $request) {
    return ['query' => $request->query->get('q', '')];
});
```

JSON üçün `$request->getContent()` məzmununu `JSON_THROW_ON_ERROR` istifadə edərək dekodlaşdırın, üst səviyyə strukturunu yoxlayın və sahələri doğrulayın. Forma məlumatları `$request->request` vasitəsilə əlçatandır.

## Cavab köməkçiləri

```php
Response::json(['created' => true], 201);
Response::html('<h1>Hello</h1>');
Response::redirect('/dashboard');
```

Yönləndirmə köməkçisi lokal mütləq yolları qəbul edir və xarici və ya protokola nəzərən nisbi hədəfləri rədd edir. Xam HTML qaytararkən daxil edilən dəyərləri özünüz ekranlaşdırmalısınız; Twig şablonları ekranlaşdırmanı avtomatik həyata keçirir.

## Xətalar

Status və ictimaiyyətə göstərilməsi təhlükəsiz olan mesajla `Elang\Http\HttpException` atın. API xətaları `message` və `request_id` olan JSON zərfindən istifadə edir. Daxili xətalar PSR-3 vasitəsilə jurnala yazılır; çağırış izi ziyarətçilərə göndərilmir.
