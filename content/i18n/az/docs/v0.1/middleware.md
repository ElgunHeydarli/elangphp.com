# Aralıq proqram təminatı

Aralıq proqram təminatı sorğunun növbəti mərhələsinin ətrafında işləyir. Elang Middleware interfeysini reallaşdırın.

## Aralıq proqram təminatı yaradın

```php
namespace App;

use Closure;
use Elang\Contracts\Middleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AddHeader implements Middleware
{
    public function process(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Application', 'My Elang app');
        return $response;
    }
}
```

## Aralıq proqram təminatını qeydiyyata alın

Qlobal aralıq proqram təminatı üçün başlanğıc faylınızda `$app->middleware(AddHeader::class)`, marşrut üçün isə `->middleware(AddHeader::class)` istifadə edin. Qeydiyyat ardıcıllığı sorğunun icra ardıcıllığıdır; cavablar əks ardıcıllıqla geri qayıdır.

## Sessiyalar və CSRF

```php
use Elang\Http\Middleware\SessionMiddleware;
use Elang\Http\Middleware\CsrfMiddleware;

$app->middleware(SessionMiddleware::class, CsrfMiddleware::class);
```

Sessiya aralıq proqram təminatı CSRF aralıq proqram təminatından əvvəl işləməlidir. Bundan sonra təhlükəsiz olmayan metodlar `_token` daxilində və ya `X-CSRF-Token` başlığında uyğun sessiya tokeni tələb edir. Vəziyyət saxlamayan API son nöqtələrinə ayrıca autentifikasiya yanaşması lazımdır; təkcə CSRF qorunması çağıran tərəfin kimliyini təsdiqləmir.

## Sorğu tezliyinin məhdudlaşdırılması

İctimai API qruplarına `RateLimitMiddleware` qoşun. O, bir host üzərində ortaq fayl sistemi keşi və proses kilidləri istifadə edir. Çoxserverli yerləşdirmələrə ortaq limit saxlanma yeri və kilidləmə arxa sistemi lazımdır.
