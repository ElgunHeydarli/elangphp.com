# Testlər

Freymvork repozitoriyasına HTTP davranışı, konteyner, aralıq proqram təminatı, məlumat yoxlaması, təhlükəsizlik, verilənlər bazası əməliyyatları və sənədləşmə marşrutları üçün PHPUnit testləri daxildir.

## Test dəstini işə salın

```bash
composer test
composer analyse
composer audit
```

Testlər və statik təhlil üçün freymvorkun inkişaf asılılıqları lazımdır. İstehsal asılılıqlarını `composer install --no-dev` ilə yalnız ayrıca istehsal artefaktında quraşdırın.

## Testdə HTTP sorğusu edin

```php
use Elang\Application;
use Symfony\Component\HttpFoundation\Request;

$app = new Application($projectPath, ['debug' => false]);
$app->router->get('/hello', fn () => ['message' => 'Hello']);
$response = $app->handle(Request::create('/hello'));

self::assertSame(200, $response->getStatusCode());
self::assertSame('Hello', json_decode($response->getContent(), true)['message']);
```

Təcrid olunmuş saxlama üçün müvəqqəti layihə qovluğu istifadə edin. Öz tətbiqlərinizdə avtorizasiyanı, təhlükəli giriş məlumatlarını, uğursuzluq cavablarını və verilənlər bazası davranışını sınaqdan keçirin.

## Uyğunluq

CI iş axını PHP 8.3 və 8.4 versiyalarını hədəfləyir. Keçən test dəsti testlərin əhatə etdiyi davranışları yoxlayır; bu, təhlükəsizlik auditi və ya başqa freymvork üzərində performans üstünlüyünə sübut deyil.
