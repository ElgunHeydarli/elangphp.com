# Yoxlamalı son nöqtə

Canlı sınaq mühitinin yoxlama son nöqtəsi burada göstərilən eyni məhdudiyyətlərdən istifadə edir.

## Məlumatları yoxlayın

```php
use Elang\Validation\Validator;
use Symfony\Component\Validator\Constraints as Assert;
$data = ['name' => 'Ada', 'email' => 'ada@example.com'];
$valid = $validator->validate($data, [
    'name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(min: 2, max: 80)],
    'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()],
]);
```

## Canlı son nöqtəni sınayın

```bash
curl -X POST https://elangphp.com/api/playground/validate   -H 'Content-Type: application/json'   -d '{"name":"Ada","email":"ada@example.com"}'
```

## Gözlənilən davranış

Etibarlı giriş 200 statusu və yoxlanmış sahələri qaytarır. Etibarsız e-poçt sahə xətası ilə 422 qaytarır. Yanlış qurulmuş JSON 400, dəstəklənməyən məzmun tipləri isə 415 qaytarır. Bu son nöqtə yalnız yoxlayır: məktub göndərmir və istifadəçi girişini saxlamır.
