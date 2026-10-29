# Middleware

Middleware оборачивает следующий этап обработки запроса. Реализуйте интерфейс Middleware Elang.

## Создание middleware

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

## Регистрация middleware

Используйте `$app->middleware(AddHeader::class)` в файле начальной загрузки для глобального middleware или `->middleware(AddHeader::class)` для middleware маршрута. Запрос проходит middleware в порядке регистрации, а ответ возвращается через них в обратном порядке.

## Сессии и CSRF

```php
use Elang\Http\Middleware\SessionMiddleware;
use Elang\Http\Middleware\CsrfMiddleware;

$app->middleware(SessionMiddleware::class, CsrfMiddleware::class);
```

Middleware сессии должен выполняться перед middleware CSRF. После этого небезопасные методы требуют совпадающий токен сессии в `_token` или заголовке `X-CSRF-Token`. Для API-эндпоинтов без состояния нужна собственная схема аутентификации; сама по себе защита CSRF не аутентифицирует вызывающую сторону.

## Ограничение частоты запросов

Подключайте `RateLimitMiddleware` к публичным группам API. При REDIS_URL он использует строгое хранилище ограничителя на Redis и распределённые блокировки. Без Redis используются файловое хранилище и межпроцессные блокировки на одном хосте. Сбои Redis сообщаются явно, без незаметного перехода к независимым локальным ограничениям.
