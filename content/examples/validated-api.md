# A validated endpoint

The live playground's validation endpoint uses the same constraints shown here.

## Validate the data

```php
use Elang\Validation\Validator;
use Symfony\Component\Validator\Constraints as Assert;
$data = ['name' => 'Ada', 'email' => 'ada@example.com'];
$valid = $validator->validate($data, [
    'name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(min: 2, max: 80)],
    'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()],
]);
```

## Try the live endpoint

```bash
curl -X POST https://elangphp.com/api/playground/validate   -H 'Content-Type: application/json'   -d '{"name":"Ada","email":"ada@example.com"}'
```

## Expected behavior

Valid input returns status 200 and the validated fields. An invalid email returns 422 with a field error. Malformed JSON returns 400; unsupported content types return 415. This endpoint validates only: it sends no mail and stores no user input.
