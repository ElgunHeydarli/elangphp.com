# Validation

Elang wraps Symfony Validator constraints. Rules are objects, so you can use the existing constraint ecosystem without another string-based rule language.

## Validate a payload

```php
use Elang\Validation\Validator;
use Symfony\Component\Validator\Constraints as Assert;

$validated = $validator->validate($data, [
    'name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(min: 2, max: 80)],
    'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()],
]);
```

Inject `Validator` into a handler or controller. A successful validation returns only fields listed in the rules. Unknown fields are not included in the returned array.

## Required and optional fields

Most constraints accept null or empty input. Add `NotBlank` or `NotNull` for required values. Use `Type` when the incoming type matters. Validation does not silently coerce input.

## Error responses

A failure throws `ValidationException`. The HTTP kernel returns status 422:

```json
{
  "message": "The submitted data is invalid.",
  "errors": {"email": ["This value is not a valid email address."]},
  "request_id": "a request correlation identifier"
}
```

Form applications can catch the exception and render their own form errors. Constraint messages can be customized using Symfony's constraint options.
