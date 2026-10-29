# Poçt

Elang Symfony Mailer-i konfiqurasiya edilmiş nəqlə bağlayır. Real nəql konfiqurasiya edilənədək heç bir mesaj göndərilmir.

## Nəqli konfiqurasiya edin

```dotenv
MAILER_DSN="smtp://user:password@smtp.example.com:587"
```

Giriş məlumatlarını URL kodlaşdırması ilə yazın. Təminatçıya xas şifrələməni və seçimləri Symfony Mailer sənədləşməsinə uyğun konfiqurasiya edin. Standart `null://null` nəqli mesajları məqsədli şəkildə göndərmədən silir.

## Mesaj göndərin

```php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

$message = (new Email())
    ->from('hello@example.com')
    ->to('reader@example.com')
    ->subject('Welcome')
    ->text('Your application is ready.');

$mailer->send($message);
```

Tətbiq xidmətinizə MailerInterface yeridin. Yoxlanılmış alıcı ünvanları və tətbiqin idarə etdiyi göndərən ünvanlarından istifadə edin.

## Çatdırılma modeli

Göndərmə sinxrondur. Çatdırılma konfiqurasiya edilmiş təminatçınızdan, DNS-dən, giriş məlumatlarından və nəqlin davranışından asılıdır. Fon rejimində çatdırılma üçün alıcını yükləyən və yeridilmiş MailerInterface vasitəsilə göndərmə edən tətbiq tapşırığı qeydiyyata alın, sonra onu /docs/v0.1/queues vasitəsilə növbəyə göndərin. Məktubların avtomatik tapşırığa çevrilməsi və tranzaksiya e-poçtu şablonları kataloqu daxil deyil.
