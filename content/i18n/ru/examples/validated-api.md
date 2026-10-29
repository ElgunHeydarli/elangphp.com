# Эндпоинт с валидацией

Эндпоинт валидации работающей интерактивной площадки использует те же ограничения, что показаны здесь.

## Валидация данных

```php
use Elang\Validation\Validator;
use Symfony\Component\Validator\Constraints as Assert;
$data = ['name' => 'Ada', 'email' => 'ada@example.com'];
$valid = $validator->validate($data, [
    'name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(min: 2, max: 80)],
    'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()],
]);
```

## Попробуйте работающий эндпоинт

```bash
curl -X POST https://elangphp.com/api/playground/validate   -H 'Content-Type: application/json'   -d '{"name":"Ada","email":"ada@example.com"}'
```

## Ожидаемое поведение

Допустимый ввод возвращает статус 200 и проверенные поля. Неверный адрес электронной почты возвращает 422 с ошибкой поля. Некорректный JSON возвращает 400; неподдерживаемые типы содержимого — 415. Этот эндпоинт только проверяет данные: он не отправляет почту и не сохраняет пользовательский ввод.
