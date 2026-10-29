# Elang ilə PHP REST API qurun

Bu təlimat kiçik, yalnız oxumağa imkan verən kataloq API-si və ayrıca giriş yoxlama son nöqtəsi qurur. JSON qaytaracaq, marşrut parametri qəbul edəcək, HTTP status kodlarından istifadə edəcək və yanlış qurulmuş girişi rədd edəcəksiniz. Verilənlər bazasında daimi saxlama əlavə etməzdən əvvəl HTTP davranışını anlaya bilməyiniz üçün kataloq məqsədli şəkildə yaddaşda saxlanılır.

## Tətbiqi yaradın

Yeni tətbiq üçün ana qovluqdan bunu yerinə yetirin:

```bash
composer create-project elang/elang catalog-api dev-main
cd catalog-api
php elang doctor
```

Bu terminalı yeni tətbiq qovluğunda saxlayın. `routes/web.php` faylını aşağıdakı tam faylla əvəz edin.

## Son nöqtələri qeydiyyata alın

```php
<?php
declare(strict_types=1);

use Elang\Http\HttpException;
use Elang\Http\Middleware\RateLimitMiddleware;
use Elang\Validation\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

$catalog = [
    1 => ['id' => 1, 'title' => 'Learn routing'],
    2 => ['id' => 2, 'title' => 'Validate input'],
];

$router->group('/api', [RateLimitMiddleware::class], function ($router) use ($catalog) {
    $router->get('/lessons', fn () => ['data' => array_values($catalog)]);

    $router->get('/lessons/{id}', function (int $id) use ($catalog) {
        if (!isset($catalog[$id])) {
            throw new HttpException(404, 'This lesson does not exist.');
        }
        return ['data' => $catalog[$id]];
    })->where('id', '[0-9]+');

    $router->post('/validate-contact', function (Request $request, Validator $validator) {
        if (!str_contains($request->headers->get('Content-Type', ''), 'application/json')) {
            throw new HttpException(415, 'Send an application/json request.');
        }
        try {
            $data = json_decode($request->getContent(), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException(400, 'Invalid JSON body.');
        }
        if (!is_array($data) || array_is_list($data) && $data !== []) {
            throw new HttpException(400, 'Expected a JSON object.');
        }
        $valid = $validator->validate($data, [
            'name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 80)],
            'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()],
        ]);
        return ['message' => 'Input accepted; nothing was stored.', 'data' => $valid];
    });
});
```

Bütün dəyərlər və marşrutlar faylda təyin edilib. Çatışmayan kontroller və ya verilənlər bazası addımı yoxdur. Yoxlama son nöqtəsi hesab yaratmır, e-poçt göndərmir və ya qeyd saxlamır.

## Serveri başladın

```bash
php elang serve
```

Serveri birinci terminalda işlək saxlayaraq sorğular üçün ikinci terminal açın. Windows PowerShell-də cURL icra faylı üçün `curl.exe` və ya metod, başlıqlar və JSON gövdəsi təyin etməyə imkan verən HTTP müştərisi istifadə edin.

## Kolleksiyanı və elementi oxuyun

```bash
curl http://127.0.0.1:8000/api/lessons
curl http://127.0.0.1:8000/api/lessons/1
```

Birinci cavab `data` daxilində hər iki dərs obyektini ehtiva edir. İkinci cavab ID-si 1 olan dərsi ehtiva edir. `/api/lessons/999` sorğusu mesaj və sorğu ID-si ilə 404 qaytarır. Rəqəm olmayan identifikator marşrut tələbinə uyğun gəlmir.

## Etibarlı JSON göndərin

POSIX əmr örtüyündə yerinə yetirin:

```bash
curl -X POST http://127.0.0.1:8000/api/validate-contact \
  -H 'Content-Type: application/json' \
  -d '{"name":"Ada","email":"ada@example.com"}'
```

Alternativ olaraq HTTP müştərisi istifadə edin: POST seçin, URL-i daxil edin, `Content-Type: application/json` əlavə edin və JSON obyektini onun xam sorğu gövdəsinə yapışdırın. Gözlənilən status: 200. Cavab yalnız yoxlanmış sahələri təkrarlayır və heç nəyin saxlanmadığını izah edir.

## Uğursuzluq hallarını sınayın

| Sorğu | Gözlənilən nəticə |
| --- | --- |
| Yanlış qurulmuş JSON | Təhlükəsiz mesajla 400 |
| JSON Content-Type yoxdur | 415 |
| Etibarsız e-poçt | email sahəsi xətası ilə 422 |
| GET kolleksiya marşrutuna POST | Allow ilə 405 |
| Naməlum dərs ID-si | 404 |
| Konfiqurasiya edilmiş limiti aşan sorğular | Retry-After ilə 429 |

Bu halları avtomatlaşdırılmış API test dəstinin başlanğıcı kimi istifadə edin. Təkcə uğurlu sorğu giriş sərhədinin düzgün davranıb-davranmadığını göstərmir.

## Daimi saxlamanı düşünülmüş şəkildə əlavə edin

Növbəti addım [verilənlər bazasına giriş](/docs/v0.1/database), sonra isə [miqrasiyalardır](/docs/v0.1/migrations). Bütün istifadəçi dəyərlərini sorğu parametrləri kimi bağlayın. İctimai tətbiqə yazma son nöqtələri əlavə etməzdən əvvəl qeydi kimin yarada və ya dəyişə biləcəyinə qərar verin.

Bu nümunənin kimlik və ya avtorizasiya sistemi yoxdur, çünki o yalnız tətbiqin idarə etdiyi kataloqu təqdim edir və saxlanmayan məlumatı yoxlayır. Autentifikasiya, sahiblik yoxlamaları və daimi yazma əməliyyatları əlavə etmək ayrıca layihələndirmə tapşırığıdır.

## Öyrənməyə davam edin

Vəziyyəti dəyişən formalar, sessiya saxlaması, CSRF qorunması və yoxlama rəyi olan HTML tətbiqi üçün [tam tapşırıq lövhəsini](/learn/build-a-task-board) izləyin. Xüsusi status və ya başlıqlara ehtiyacınız olduqda [HTTP cavabları istinadından](/docs/v0.1/controllers) istifadə edin.
