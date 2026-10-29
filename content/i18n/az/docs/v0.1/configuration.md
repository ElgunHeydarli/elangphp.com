# Konfiqurasiya

Konfiqurasiya `config/app.php` faylında yerləşir. Bu fayl PHP massivi qaytarır; mühitə xas dəyərlər versiya nəzarətində izlənməyən `.env` faylında saxlanılır.

## Mühit dəyərləri

```dotenv
APP_ENV=local
APP_DEBUG=false
APP_URL=http://localhost:8000
APP_KEY=
```

`php elang key:generate` ilə şifrələmə açarı yaradın. Əmr açarı çap etmədən `.env` faylında saxlayır. Açarın dəyişdirilməsi əvvəl şifrələnmiş məlumatları oxunmaz edir.

## Konfiqurasiyanı oxuyun

```php
use Elang\Support\Config;

$router->get('/application', fn (Config $config) => [
    'name' => $config->get('name', 'My application'),
]);
```

Nöqtə ilə ayrılmış açarlar iç-içə massivlərdə hərəkət edir. `Config::env()` konfiqurasiya faylları üçün nəzərdə tutulub ki, tətbiqinizin qalan hissəsi həll edilmiş konfiqurasiyadan asılı ola bilsin.

## İstehsal mühiti üçün standartlar

`APP_ENV=production`, `APP_DEBUG=false` və kanonik HTTPS `APP_URL` dəyərlərini təyin edin. Giriş məlumatlarını ictimai qovluqdan kənarda saxlayın. `MAILER_DSN` konfiqurasiya edilənədək standart poçt nəqli mesajları göndərmədən silir.

## Sorğu limitləri

Saytda `max_body_bytes` 16 KiB, `rate_limit` isə müəyyən edilmiş hər müştəri IP ünvanı üçün dəqiqədə 120 API sorğusu olaraq təyin edilib. Öz tətbiqiniz üçün uyğun limitlər təyin edin. Əks proksiyə etibar lazım olduqda açıq şəkildə konfiqurasiya edilməlidir; ixtiyari ötürülmüş başlıqlara standart olaraq etibar edilmir.

## Konteyner üçün sirr faylları

`APP_KEY_FILE` və `MAILER_DSN_FILE` dəyərləri konteyner konfiqurasiyasında açıq göstərmək əvəzinə mütləq yollu, oxuna bilən şəxsi fayllara istinad edə bilər. Açıq şəkildə təyin edilmiş, boş olmayan `APP_KEY` və ya `MAILER_DSN` üstünlük daşıyır. Fayllar 8192 baytla məhdudlaşdırılır; konfiqurasiya edilmiş fayl çatışmadıqda və ya etibarsız olduqda açarlar səssizcə dəyişdirilmir, konfiqurasiya xəta ilə dayanır. İcazələri məhdud saxlayın və HTTP, CLI, növbə və soket proseslərində eyni tətbiq açarını qoruyun.

Lokal Compose steki inkişaf açarını şəxsi yaddaşda saxlayır və `APP_KEY_FILE` təyin edir, buna görə `docker compose exec app php elang doctor` veb prosesi ilə eyni açarı görür. Sirr faylı mənbəyi konfiqurasiya edildikdə `key:generate` alternativ `.env` açarı yaratmaqdan imtina edir. İstehsal açar fayllarını yerləşdirmənizin sirr meneceri vasitəsilə idarə edin; adi yeniləmə zamanı açarları dəyişməyin.
