# Tətbiqdaxili bildirişlər və növbəyə alınmış e-poçt

Şəxsi gələnlər qutusu və istəyə bağlı fon poçtu üçün bildiriş xidmətindən istifadə edin. SQL gələnlər qutusuna əlavəetmə və növbəyə göndərmə eyni verilənlər bazası tranzaksiyasından istifadə edir. E-poçt çatdırılması ayrıca nəzarət edilən işçidə işləyir. Bu, mövcud sinxron hesab təsdiqləmə/parol bərpa nəqlindən ayrıdır.

## Hazırlayın və qeydiyyata alın

```bash
php elang platform:setup
```

Təminatçını həm HTTP sorğularının, həm də işçilərin istifadə etdiyi başlanğıc faylında qeydiyyata alın:

```php
$app->provider(new Elang\Notification\Provider());
```

Təminatçı sabit `notification.mail` emaledicisini qeydiyyata alır. JSON məzmunu ixtiyari PHP siniflərini və ya kodu seçə bilməz. Poçt məzmunu növbəyə daxil olmadan əvvəl `APP_KEY` ilə şifrələnir. Bu açarı dəyişmədən saxlayın və ehtiyat nüsxəsini çıxarın. Gələnlər qutusu mətni avtorizasiya vasitəsilə şəxsidir, lakin saxlanarkən avtomatik şifrələnmir; məlumatlarınıza uyğun verilənlər bazası/disk şifrələməsi tətbiq edin.

## Göndərin və oxuyun

```php
$notifications = $app->container->get(Elang\Notification\Notifications::class);
$id = $notifications->send(
    $user,
    'Export ready',
    'Your requested report is ready to review.',
    '/reports/42',
    mail: true,
);
$inbox = $notifications->inbox($user);
$notifications->markRead($user, $id);
```

Mövzular 160 simvol, gövdələr isə 16.000 bayt ilə məhdudlaşdırılır. İstəyə bağlı keçidlər ən çox 500 baytlıq lokal yollar olmalıdır; protokola nəzərən nisbi keçidlər, əks kəsr xətləri, idarəetmə simvolları və boşluqlar rədd edilir. Mətni şablon ekranlaşdırması ilə göstərin. Gələnlər qutusu sorğuları və oxunma yeniləmələri həmişə `user_id` ilə məhdudlaşdırılır; başqa istifadəçi mesajınızı oxunmuş kimi işarələyə bilməz. Xidmət ən son 100 gələnlər qutusu qeydini qaytarır.

Bildiriş keçidi avtorizasiya deyil, naviqasiyadır. Hədəf yenə də qeydin sahibliyini/komanda üzvlüyünü yoxlamalıdır. Təşkilat məlumatları üçün gələnlər qutusu/e-poçt mətninə yalnız minimum faydalı məlumatı daxil edin: üzvün silinməsi ona artıq çatdırılmış e-poçtu silə bilməz.

## Real e-poçtu konfiqurasiya edin

```dotenv
MAILER_DSN=smtp://username:encoded-password@smtp.example.com:587
MAIL_FROM=notifications@example.com
```

Həqiqi dəyərləri Git-dən kənarda saxlayın və DSN daxilində giriş məlumatlarını URL kodlaşdırması ilə yazın. Sirr menecerinə və ya qorunan mühit faylına üstünlük verin. Nəql təminatçısının sənədləşdirilmiş TLS parametrlərindən istifadə edin. Göndərən domen üçün SPF, DKIM və DMARC konfiqurasiya edin, icazəli göndərəni təsdiqləyin və geri qayıdan məktub/şikayət emalını sınaqdan keçirin. Təkcə konfiqurasiya edilmiş DSN çatdırılmanı və ya domen nüfuzunu sübut etmir.

```bash
php elang queue:work
php elang queue:failed
```

İşçinin poçtu səssizcə silməməsi üçün növbəyə alınmış bildiriş çatdırılması `null://null` nəqlini məqsədli şəkildə rədd edir. Mailpit kimi inkişaf SMTP tutma serveri mesajları həqiqi alıcılara göndərmədən qəbul edir. Hesab təsdiqləməsi/bərpası nəql null olduqda `APP_ENV=local` daxilində artıq şifrələnmiş lokal poçt qutusu təklif edir; həmin poçt qutusu istehsal poçt xidməti deyil, inkişaf rahatlığıdır.

## Təkrar cəhd və çatdırılma semantikası

İşçi tapşırığı rezervasiya tokeni ilə qorunan müddətə götürür, məhdud gecikmə ilə təkrar cəhd edir və mesaj gövdələri və ya giriş məlumatları olmadan istisna sinfini qeydə alır. Uğursuz tapşırıq metadatasını yoxlayın, səbəbi düzəldin, sonra nəzərdən keçirilmiş tapşırığı yenidən sınayın:

```bash
php elang queue:retry JOB_ID_FROM_FAILED_LIST
```

Çatdırılma ən azı bir dəfə baş verir. SMTP serveri mesajı qəbul edir, lakin işçi qəbul təsdiqini itirir və ya tapşırığı tamamlamazdan əvvəl dayanırsa, təkrar cəhd onu yenidən göndərə bilər. İdempotentlik dəstəkləyən təminatçılar üçün sabit çatdırılma açarı və təminatçıya xas adapter əlavə edin. Heç vaxt e-poçtun məhz bir dəfə göndəriləcəyini vəd etməyin.

Tapşırıqları konfiqurasiya edilmiş rezervasiya müddətindən qısa saxlayın və ya nəzərdən keçirilmiş uzatma siyasəti reallaşdırın. Xam təsdiqləmə keçidlərini, tokenləri, parolları və ya şəxsi məlumatları şifrələnməmiş növbə məzmununa daxil etməyin. Növbə gecikməsini, uğursuzluqları, işçi aktivlik siqnallarını və çıxış qutusu yığılmasını izləyin. [İstismar](/docs/v0.1/observability), [poçt](/docs/v0.1/mail) və [növbələr](/docs/v0.1/queues) bölmələrinə baxın.
