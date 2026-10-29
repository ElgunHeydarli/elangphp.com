# Təhlükəsizlik və sessiyalar

Elang təhlükəsizlik mexanizmləri və cavablar üçün standartlar təqdim edir. Tətbiqinizə yenə də tam autentifikasiya, avtorizasiya və yerləşdirmə yanaşması lazımdır.

## Parollar

```php
use Elang\Security\Password;

$hash = $passwords->hash($plainTextPassword);
$isValid = $passwords->verify($submittedPassword, $storedHash);
```

Xidmət PHP-nin Argon2id parol API-sindən istifadə edir. Uğurlu yoxlamadan sonra saxlanan heşin yenilənməyə ehtiyacı olub-olmadığını müəyyən etmək üçün `needsRehash()` istifadə edin. PHP yığımınızın Argon2id dəstəklədiyinə əmin olun.

## Şifrələmə

```php
use Elang\Security\Encryption;

$ciphertext = $encryption->encrypt('private value');
$plaintext = $encryption->decrypt($ciphertext);
```

Sodium secretbox şifrəli mətnin həqiqiliyini yoxlayır və hər dəfə yeni nonce istifadə edir. APP_KEY yoxdursa və ya etibarsızdırsa, şifrələmə xidməti yaradıla bilmir. Açarı təhlükəsiz saxlayın; bu ilkin versiyada açarın dəyişdirilməsi əl ilə aparılır.

## Sessiyalar və CSRF

SessionMiddleware və ardınca CsrfMiddleware qeydiyyata alın. Forma yaradarkən `Elang\Security\Csrf` yeridin və `$csrf->token($request)` çağırın. Tokeni gizli `_token` sahəsinə və ya `X-CSRF-Token` başlığına daxil edin.

Sessiya kukiləri HttpOnly və SameSite=Lax, sorğu HTTPS olduqda isə əlavə olaraq Secure istifadə edir. Autentifikasiyadan sonra `$request->getSession()->migrate(true)` ilə sessiya ID-sini yenidən yaradın.

## Cavabların qorunması

Nüvə məhdudlaşdırıcı Məzmun Təhlükəsizliyi Siyasəti, çərçivədə göstərmənin qadağan edilməsi, MIME tipinin təxmin edilməsinə qarşı qoruma və istinad mənbəyi siyasəti təyin edir. JSON və HTML xətaları sorğu identifikatorları daşıyır. İstehsal mühitinin xəta cavabları daxili istisna mesajlarını gizlədir.

## Problem bildirin

İctimai problem qeydində giriş məlumatları və ya boşluqdan istifadə təfərrüatları paylaşmayın. Cari bildiriş qaydaları üçün repozitoriyadakı SECURITY.md faylına baxın. Bu ilkin versiya müstəqil təhlükəsizlik auditindən keçməyib.


### Tətbiqə məxsus Content Security Policy

Framework standart olaraq yalnız öz mənbələrinə icazə verən məhdudlaşdırıcı CSP təqdim edir. Tətbiq standart başlıqlar tətbiq olunmazdan əvvəl cavabda ayrıca `Content-Security-Policy` başlığı təyin edə bilər. Siyasəti serverdə idarə edin və yalnız ehtiyacı olan marşrutla məhdudlaşdırın; etibar edilməyən sorğu başlıqlarından yaratmayın. Rəsmi sənədləşmə saytı sazlanmış analitika domenlərinə yalnız açıq səhifələrdə və ziyarətçinin icazəsindən sonra müraciət edir; hesab və administrator səhifələri məhdudlaşdırıcı standart siyasəti saxlayır.
