# Валидация

Elang предоставляет оболочку над ограничениями Symfony Validator. Правила задаются объектами, поэтому вы можете пользоваться существующей экосистемой ограничений без дополнительного строкового языка правил.

## Валидация входных данных

```php
use Elang\Validation\Validator;
use Symfony\Component\Validator\Constraints as Assert;

$validated = $validator->validate($data, [
    'name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(min: 2, max: 80)],
    'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()],
]);
```

Внедрите `Validator` в обработчик или контроллер. При успешной валидации возвращаются только поля, перечисленные в правилах. Неизвестные поля не включаются в возвращаемый массив.

## Обязательные и необязательные поля

Большинство ограничений допускает null или пустой ввод. Добавляйте `NotBlank` или `NotNull` для обязательных значений. Используйте `Type`, когда важен тип входных данных. Валидация не выполняет неявное преобразование типов.

## Ответы с ошибками

При неудаче выбрасывается `ValidationException`. HTTP-ядро возвращает статус 422:

```json
{
  "message": "The submitted data is invalid.",
  "errors": {"email": ["This value is not a valid email address."]},
  "request_id": "a request correlation identifier"
}
```

Приложения с формами могут перехватить исключение и отобразить собственные ошибки формы. Сообщения ограничений можно настраивать с помощью параметров ограничений Symfony.
