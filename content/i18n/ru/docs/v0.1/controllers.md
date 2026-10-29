# Контроллеры и ответы

Контроллеры позволяют оставлять в файлах маршрутов только нужные определения. Elang разрешает зависимости конструктора и параметры обработчиков через контейнер сервисов.

## Вызываемый контроллер

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

Зарегистрируйте его с помощью `$router->get('/hello', HelloController::class)`.

## Чтение запроса

```php
use Symfony\Component\HttpFoundation\Request;

$router->get('/search', function (Request $request) {
    return ['query' => $request->query->get('q', '')];
});
```

Для JSON декодируйте `$request->getContent()` с использованием `JSON_THROW_ON_ERROR`, проверьте структуру верхнего уровня и выполните валидацию полей. Данные формы доступны через `$request->request`.

## Вспомогательные методы ответа

```php
Response::json(['created' => true], 201);
Response::html('<h1>Hello</h1>');
Response::redirect('/dashboard');
```

Метод перенаправления принимает локальные абсолютные пути и отклоняет внешние адреса и адреса без указания протокола. При возврате HTML напрямую нужно экранировать подставляемые значения; шаблоны Twig делают это автоматически.

## Ошибки

Выбрасывайте `Elang\Http\HttpException` со статусом и безопасным публичным сообщением. Ошибки API используют JSON-объект с полями `message` и `request_id`. Внутренние ошибки записываются в журнал через PSR-3; трассировки стека посетителям не отправляются.
