# Autentifikasiya və hesab təhlükəsizliyi

Elang-ın Auth Starter layihəsinə qeydiyyat, sessiya ilə giriş, təsdiqləmə, parolun bərpası, profil yeniləmələri, parolun dəyişdirilməsi və səlahiyyətləri məhdudlaşdırılmış API tokenləri daxildir. Onu AuthProvider ilə aktivləşdirin; başlanğıc layihəsi bunu artıq sizin üçün edir.

## Autentifikasiyalı tətbiq yaradın

```bash
elang new my-app --starter=auth
cd my-app
php elang serve
```

Quraşdırıcının birdəfəlik hazırlanmasından sonra bu əmr Composer vasitəsilə tətbiqi yaradır. Asılılıqlar, tətbiq açarı, sxem miqrasiyaları və doctor yoxlamaları layihənin hazırlanmasına daxildir. Öz hesabınızı yaratmaq üçün `/register` açın. Ortaq administrator parolu daxil edilməyib.

## Sessiyalar və qorunan marşrutlar

AuthProvider brauzerdəki hesab marşrutlarını sessiya, CSRF və sorğu tezliyi məhdudlaşdırıcısı aralıq proqram təminatı arxasında qruplaşdırır. Giriş sessiya identifikatorunu və CSRF tokenini yenidən yaradır. Çıxış sessiyanı etibarsız edir. Sorğu gövdələri və avtorizasiya dəyərləri Elang Studio tərəfindən qeydə alınmır.

Şəxsi brauzer marşrutlarında SessionMiddleware-dən sonra RequireAuth istifadə edin. O, cari User obyektini sorğunun `_user` atributuna yerləşdirir. Müvəqqəti parol şəxsi funksiyalardan istifadə etməzdən əvvəl istifadəçini `/password` ünvanına yönləndirir.

```php
$router->get('/private', fn () => ['message' => 'Signed in'])
    ->middleware(SessionMiddleware::class, RequireAuth::class);
```

Vəziyyəti dəyişən formalar üçün başlanğıc layihəsində göstərilmiş, CsrfMiddleware daxil olmaqla tam aralıq proqram təminatı qrupundan istifadə edin. Qorunan marşrutda yenə də tətbiqə xas sahiblik və ya siyasət yoxlamaları lazımdır.

## Parollar və bərpa

İctimai istifadəçi parolları ən azı 12 simvol tələb edir və uzun parol ifadələrini dəstəkləyir. Saxlanan dəyər Argon2id heşidir. Etibarlı terminal ilk girişdə dəyişdirilməsi məcburi olan müvəqqəti parol yarada bilər.

Bərpa və təsdiqləmə keçidləri təsadüfi birdəfəlik tokenlər istifadə edir. Verilənlər bazasında yalnız token heşləri, onların məqsədi və bitmə vaxtı saxlanılır. Uğurlu parol dəyişikliyi digər sessiyaları etibarsız edir və API tokenlərini ləğv edir. Parolun sıfırlanması istifadəçini yeni brauzerə səssizcə daxil etmir.

## Lokal və istehsal mühitində e-poçt

APP_ENV=local və null poçt nəqli olduqda hesab mesajları şəxsi inkişaf poçt qutusunda şifrələnmiş saxlanılır. Təsdiqləmə və ya sıfırlama keçidindən istifadə etmək üçün lokal olaraq `/_elang/studio/mail` açın.

İstehsal mühitində MAILER_DSN və MAIL_FROM konfiqurasiya edin. Null nəql hesab məktublarını çatdırdığını iddia etmir; bərpa xidmətin əlçatan olmadığını bildirir. Lokal poçt qutusu və Studio istehsal mühitində açıq deyil.

## Avtorizasiya və rollar

Qeydiyyat həmişə adi istifadəçi yaradır. Göndərilmiş rol sahələri qəbul edilmir. Etibarlı hazırlama prosesi administrator yarada bilər və RequireAdmin e-poçt təsdiqini də tələb edir. Gate tətbiqə açıq siyasətlər təyin etməyə imkan verir:

```php
$gate->define('post.update', fn (User $user, Post $post) =>
    $post->author_id === $user->id
);
$gate->authorize('post.update', $user, $post);
```

Oxuma, yeniləmə və silmə daxil olmaqla verilənlər bazası sorğularını cari istifadəçinin identifikatoru ilə məhdudlaşdırın. Tam bloq, iş sahəsi və API başlanğıc layihələri bu sərhədi nümayiş etdirir.

## API tokenləri

Təsdiqləmədən və parolun təsdiqindən sonra hesab səhifəsindən tokenlər yaradın. Xam token bir dəfə göstərilir. Verilənlər bazası sətirlərində SHA-256 xülasəsi, açıq səlahiyyət dairələri, sahib və bitmə vaxtı saxlanılır. BearerAuth tokenin kimliyini təsdiqləyir; RequireScope marşrutun standart `_scope` dəyərini yoxlayır. Tokenin sahibi heç vaxt sorğu gövdəsindən götürülmür.
