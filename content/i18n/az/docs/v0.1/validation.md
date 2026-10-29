# Məlumatların yoxlanması

Elang Symfony Validator məhdudiyyətlərini özündə birləşdirir. Qaydalar obyektlərdir, buna görə başqa bir sətir əsaslı qayda dili olmadan mövcud məhdudiyyət ekosistemindən istifadə edə bilərsiniz.

## Göndərilən məlumatları yoxlayın

```php
use Elang\Validation\Validator;
use Symfony\Component\Validator\Constraints as Assert;

$validated = $validator->validate($data, [
    'name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(min: 2, max: 80)],
    'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()],
]);
```

`Validator` obyektini emalediciyə və ya kontrollerə yeridin. Uğurlu yoxlama yalnız qaydalarda göstərilmiş sahələri qaytarır. Naməlum sahələr qaytarılan massivə daxil edilmir.

## Məcburi və istəyə bağlı sahələr

Əksər məhdudiyyətlər null və ya boş girişi qəbul edir. Məcburi dəyərlər üçün `NotBlank` və ya `NotNull` əlavə edin. Daxil olan tip əhəmiyyətli olduqda `Type` istifadə edin. Yoxlama giriş məlumatlarının tipini səssizcə çevirmir.

## Xəta cavabları

Uğursuzluq `ValidationException` yaradır. HTTP nüvəsi 422 statusu qaytarır:

```json
{
  "message": "The submitted data is invalid.",
  "errors": {"email": ["This value is not a valid email address."]},
  "request_id": "a request correlation identifier"
}
```

Forma tətbiqləri istisnanı tutub öz forma xətalarını göstərə bilər. Məhdudiyyət mesajlarını Symfony məhdudiyyətlərinin seçimləri vasitəsilə fərdiləşdirmək olar.
