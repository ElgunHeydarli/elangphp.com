# Hesab təhlükəsizliyi: MFA, keçid açarları və cihazlar

Bu təlimat [autentifikasiya](/docs/v0.1/authentication) bölməsini genişləndirir. Elang parolla girişi, istəyə bağlı vaxt əsaslı birdəfəlik parolları, keçid açarlarını, ləğv edilə bilən cihaz sessiyalarını və müdaxiləni aşkar edən audit zəncirini birləşdirir. Bu funksiyalar tətbiq qurmaq üçün əsas hissələrdir; tətbiqin bank işi və ya normativ tələblərə uyğunluğunu təsdiqləmir.

## Quraşdırın və hazırlayın

PHP 8.3 və ya daha yeni versiya, Composer 2, dəyişməyən tətbiq şifrələmə açarı, HTTPS və sinxronlaşdırılmış server vaxtı istifadə edin. Tam freymvorka OTPHP, web-auth/webauthn-lib və lokal SVG QR yaradıcısı daxildir. Heç bir üçüncü tərəf QR xidməti sirrinizi almır.

```bash
php elang key:generate
php elang platform:setup
php elang doctor
```

Mövcud istehsal verilənlər bazasının ehtiyat nüsxəsini çıxarın, sxem dəyişikliklərini nəzərdən keçirin və `php elang platform:setup --force` yerinə yetirin. Mövcud `APP_KEY` dəyərini saxlayın: onun dəyişdirilməsi saxlanmış MFA sirlərini və şifrələnmiş poçtu oxunmaz edir. Tətbiqiniz hələ qeydiyyata almırsa, başlanğıc faylına `Elang\Auth\AuthProvider` əlavə edin.

## Autentifikatoru aktivləşdirin

1. Daxil olun, müvəqqəti parolunuz varsa dəyişin və e-poçtunuzu təsdiqləyin.
2. `/security` açın, cari parolunuzu daxil edin və qurmağa başlayın.
3. Lokal QR şəklini autentifikatorla skan edin və ya göstərilən quraşdırma açarını əl ilə daxil edin.
4. Cari altırəqəmli kodu göndərin. Qurmanın vaxtı on dəqiqədən sonra bitir.
5. On bərpa kodunu şəxsi parol menecerində saxlayın. Onlar bir dəfə göstərilir.

Verilənlər bazası şifrələnmiş autentifikator sirrini və bərpa kodlarının heşlərini saxlayır. Kodlar altı rəqəmdən və 30 saniyəlik addımlardan istifadə edir, saat fərqi üçün bir addımlıq dözümlülük var. Son addımın atomik yenilənməsi qəbul edilmiş autentifikator kodunun təkrar istifadəsini rədd edir. Hər bərpa kodu bir dəfə istifadə edilə bilər. Qurmanı təsdiqləmək üçün istifadə edilmiş kod dərhal giriş üçün təkrar istifadə edilə bilməz.

MFA aktivləşdirildikdən və ya söndürüldükdən sonra digər sessiyalar, API tokenləri və qüvvədə olan parol/təsdiqləmə keçidləri ləğv edilir. Cari brauzer yeni sessiya alır. Bundan sonra parolla giriş beş dəqiqəlik gözləyən yoxlama yaradır; ikinci amil uğurlu olanadək hesaba giriş vermir. `/two-factor` brauzer formalarını, `/api/session/mfa` isə JSON müştərilərini emal edir. Hər ikisində CSRF qorunması və cəhd limitləri var.

MFA-nın söndürülməsi və bərpa kodlarının dəyişdirilməsi cari parolu və yeni autentifikator və ya bərpa kodunu tələb edir. Bərpa kodlarının dəyişdirilməsi bütün köhnə kodları etibarsız edir. E-poçtla parol bərpası parolu dəyişir və sessiyaları ləğv edir; MFA-nı silmir.

## Keçid açarlarını qeydiyyata alın və istifadə edin

`/security` daxilində parolunuzu daxil edin, cihaz adı seçin və keçid açarı qeydiyyata alın. Brauzer öz standart etimadnamə dialoqundan istifadə edir. İstifadəçi doğrulaması və aşkar edilə bilən etimadnamə tələb olunur. Elang ES256 və RS256 açıq açar alqoritmlərini və `none` attestasiyasını dəstəkləyir. Burada attestasiya heç bir cihaz sertifikatını sübut etmir.

Etibar edən tərəfin ID-si `APP_URL` host adından alınır. İstehsal mühitində HTTPS lazımdır. İnkişaf üçün loopback və localhost dəstəklənir. Bir neçə ön tərəf eyni etibar edən tərəfin host adından istifadə etdikdə dəqiq mənbələri konfiqurasiya edin:

```php
'auth' => [
    'passkey_origins' => ['https://app.example.com'],
],
```

Mənbələrə sxem, host adı və port daxildir. Ümumi uyğunluq işarələri və alt domenlərin geniş şəkildə qəbulu söndürülüb. Mənbə brauzerin etibar edən tərəf qaydalarına da uyğun gəlməlidir: əlaqəsiz domenin bu siyahıya əlavə edilməsi onları keçmir. Qeydiyyat və autentifikasiya yoxlamaları təsadüfidir, server sessiyasına bağlıdır, iki dəqiqədən sonra bitir və bir dəfə istifadə edilir. Aktiv dəstəklənən WebAuthn kitabxanası imzaları, mənbəni, yoxlama dəyərini, istifadəçi doğrulamasını və sayğac qaydalarını yoxlayır. Etimadnamə yeniləmələri optimist paralellik nəzarətindən istifadə edir.

`/login` daxilində keçid açarı ilə giriş seçimini istifadə edin. Təsdiqlənmiş keçid açarı ayrıca giriş üsuludur; parolla girişin TOTP yoxlamasını istəmir. Keçid açarının silinməsi hesab parolunu tələb edir. Son istifadəyə yararlı etimadnaməni silməzdən əvvəl başqa bərpa üsulu saxlayın.

## Cihazları və audit qeydlərini nəzərdən keçirin

`/security` cihaz sessiyalarını brauzer təsviri və son aktivliklə birlikdə sadalayır. Təsvirlər brauzerlərin təqdim etdiyi ipuclarıdır, etibarlı cihaz kimliyi deyil. Bir sessiyanın ləğv edilməsi onun növbəti avtorizasiya yoxlamasında qüvvəyə minir. Parolun dəyişdirilməsi bütün sessiyaları və API/keçid tokenlərini ləğv edir. Standart sessiya kukiləri HttpOnly, SameSite=Lax və HTTPS-də Secure istifadə edir. Redis yerləşdirmələri ləğvetmə məlumatlarını SQL, sessiya baytlarını isə Redis vasitəsilə paylaşır.

```php
$audit = $app->container->get(Elang\Audit\Audit::class);
$result = $audit->verify('user:'.$user->id);
// ['valid' => true, 'sequence' => ..., 'hash' => ...]
```

Audit qeydləri `APP_KEY` dəyərindən törədilmiş açarla hər əhatə dairəsi üçün HMAC zənciri yaradır. Tətbiq açarı etibarlı olduqda onlar sətir dəyişikliklərini, çatışmayan ardıcıllıq qeydlərini və dəyişdirilmiş zəncir başını aşkar edir. Onlar xarici dəyişməz jurnal deyil. Təhdid modeliniz verilənlər bazası administratoruna qarşı müqavimət tələb edirsə, imzalanmış nəzarət nöqtələrini ayrıca etibar sərhədinə ixrac edin. Audit metadatasına heç vaxt parollar, sirlər, tokenlər, e-poçt ünvanları, sorğu gövdələri və ya ödəniş məlumatları yazmayın.

## Problemlərin aradan qaldırılması

- **Kod rədd edilir:** server və cihaz vaxtını sinxronlaşdırın; əvvəlki addım artıq istifadə edilibsə, yeni kod gözləyin. Saat problemlərini gizlətmək üçün qəbul intervalını heç vaxt artırmayın.
- **Keçid açarı dialoqu əlçatan deyil:** HTTPS/localhost, brauzer dəstəyi, dəqiq mənbə, RP host adı və platforma autentifikasiyasının əlçatanlığını yoxlayın.
- **Qurma yox oldu:** on dəqiqəlik qurmanın və ya iki dəqiqəlik brauzer yoxlamasının vaxtı bitib; qurmanı yenidən başladın.
- **Bütün cihazlardan çıxış edildi:** parol və ya amil dəyişiklikləri etimadnamə versiyalarını məqsədli şəkildə yeniləyir.
- **Bütün amillər itirilib:** saxlanmış bərpa kodundan istifadə edin. İnzibati bərpa ayrıca nəzərdən keçirilmiş kimlik yoxlama prosesi olmalıdır; Elang MFA-nı keçmək üçün ictimai son nöqtə təqdim etmir.

Növbəti: [Redis infrastrukturu](/docs/v0.1/redis) və [istismar](/docs/v0.1/observability).
