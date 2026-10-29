# İlk marşrutunuz

Bu dərs boş tətbiqi kiçik HTTP xidmətinə çevirir.

## Layihə hazırlayın

Müstəqil tətbiq yaratmaq üçün quraşdırma təlimatını izləyin. Onu `php elang serve` ilə başladın.

## Marşrut təyin edin

`routes/web.php` məzmununu bununla əvəz edin:

```php
<?php
declare(strict_types=1);
use Elang\Http\Response;
$router->get('/', fn () => Response::html('<h1>Hello, Elang.</h1>'), 'home');
$router->get('/hello/{name}', fn (string $name) => ['message' => 'Hello, '.$name.'!'], 'hello');
```

`/hello/Ada` ünvanına keçin. `{"message":"Hello, Ada!"}` almalısınız.

## Davranışı yoxlayın

Naməlum URL açın və 404 cavabını təsdiqləyin. `/hello/Ada` ünvanına POST göndərin və 405-i təsdiqləyin. Qeydiyyata alınmış metodları yoxlamaq üçün `php elang routes` yerinə yetirin.

## Özünüz sınayın

`status` və tətbiqinizin adını ehtiva edən massiv qaytaran `/status` marşrutu əlavə edin. Hər iki marşrut işlədikdə JSON API dərsinə davam edin.

## Hər sətrin yeri

Marşrut təyinatları freymvorkun mənbə qovluğunda deyil, tətbiqinizin `routes/web.php` faylında yerləşməlidir. Başlanğıc faylı həmin faylı yükləməzdən əvvəl `$router` yaradır. İnkişaf serveri sorğuları `public/index.php` vasitəsilə göndərir.

Marşrut faylı daxilində vendor/autoload.php faylını yenidən daxil etməyə ehtiyac yoxdur; ictimai giriş nöqtəsi Composer-i artıq yükləyib. use ifadəsi sinif adını qısaldır, Composer isə quraşdırılmış sinfi tapır.

## Gözlənilən cavablar

`/` açıldıqda HTML başlığı göstərilir. `/hello/Ada` açıldıqda `Content-Type: application/json` ilə JSON qaytarılır. PHP mənbəyi server tərəfindən icra olunur; brauzer heç vaxt qovluq siyahısı və ya xam marşrut faylı almamalıdır.

Brauzer PHP mənbəyini endirirsə və ya mətn kimi göstərirsə, dayanın və veb-serverin PHP emaledicisini düzəldin. Terminal serveri işləmirsə, brauzer lokal tətbiqinizə qoşula bilməz.
