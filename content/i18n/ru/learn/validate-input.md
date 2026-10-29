# Валидация пользовательского ввода

JSON-эндпоинт должен проверять тип содержимого, безопасно разбирать JSON и проверять необходимые поля.

## Добавление эндпоинта с валидацией

```php
use Elang\Http\HttpException;
use Elang\Validation\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
$router->post('/api/contact', function (Request $request, Validator $validator) {
    if (!str_contains($request->headers->get('Content-Type', ''), 'application/json')) {
        throw new HttpException(415, 'Send JSON.');
    }
    try { $data = json_decode($request->getContent(), true, 32, JSON_THROW_ON_ERROR); }
    catch (JsonException) { throw new HttpException(400, 'Invalid JSON.'); }
    if (!is_array($data)) throw new HttpException(400, 'Expected an object.');
    $valid = $validator->validate($data, [
        'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()],
    ]);
    return ['data' => $valid];
});
```

## Отправка запроса

```bash
curl -X POST http://127.0.0.1:8000/api/contact   -H 'Content-Type: application/json'   -d '{"email":"ada@example.com"}'
```

## Проверка недопустимого ввода

Повторите запрос с `not-an-email`. Убедитесь, что получен статус 422 и ошибка поля email. Этот пример выполняет валидацию без сохранения данных или аутентификации пользователей.
