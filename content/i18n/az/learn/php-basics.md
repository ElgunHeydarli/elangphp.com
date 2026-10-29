# Elang öyrənmək üçün sizə lazım olan PHP

Bu dərs freymvork nümunələrində istifadə olunan sintaksisi izah edir. Bu, tam PHP təlimatını əvəz edən mənbə deyil, məqsədyönlü girişdir. Kiçik kod parçalarını `practice.php` faylında `php practice.php` ilə işlədə bilərsiniz.

## Dəyişənlər və sətirlər

PHP dəyişənləri `$` ilə başlayır. Nöqtəli vergül ifadəni bitirir. Nöqtə operatoru sətirləri birləşdirir:

```php
<?php
declare(strict_types=1);
$name = 'Ada';
echo 'Hello, '.$name.'!';
```

Gözlənilən çıxış: `Hello, Ada!`. Dırnaq içindəki mətn sətirdir. `42` tam ədəddir, `true` isə məntiqi dəyərdir. JSON oxuyarkən və ya tipləndirilmiş funksiya parametri elan edərkən tiplər əhəmiyyətlidir.

## Massivlər

Massiv dəyərlər siyahısı və ya adlandırılmış sahələr saxlaya bilər:

```php
$tags = ['php', 'elang'];
$post = ['title' => 'A small beginning', 'published' => true];
echo $post['title'];
```

`=>` operatoru açarı dəyərə bağlayır. Elang-ın JSON cavabları çox vaxt PHP massivi kimi başlayır. JSON HTTP üzərindən göndərilən mətn formatıdır; yaddaşdakı PHP massivi ilə eyni deyil.

## Funksiyalar və qaytarılan dəyərlər

Funksiya təkrar istifadə edilə bilən davranışı qruplaşdırır. Parametr və qaytarma tipləri onun nə qəbul edib nə qaytardığını təsvir edir:

```php
function greeting(string $name): string
{
    return 'Hello, '.$name.'!';
}
echo greeting('Ada');
```

`return` dəyəri çağıran tərəfə qaytarır. `echo` çıxışı birbaşa yazır. Elang emaledicisində çıxışı özünüz çap etmək əvəzinə cavab qaytarın ki, nüvə status kodlarını və başlıqları düzgün təyin edə bilsin.

## Anonim və ox funksiyaları

Marşrut anonim funksiya qəbul edə bilər:

```php
$router->get('/hello', function () {
    return ['message' => 'Hello'];
});
```

Ox funksiyası yığcam ifadədir:

```php
$router->get('/hello', fn () => ['message' => 'Hello']);
```

Bu nümunələrdə eyni cavab nəzərdə tutulur. Funksiya yalnız marşrut qeydiyyata alındığı üçün deyil, uyğun sorğu emalediciyə çatanda işləyir.

## Siniflər və obyektlər

Sinif obyekti təsvir edir. `new` nüsxə yaradır; `->` nüsxənin metoduna və ya xüsusiyyətinə müraciət edir:

```php
final class Greeter
{
    public function greet(string $name): string
    {
        return 'Hello, '.$name.'!';
    }
}
$greeter = new Greeter();
echo $greeter->greet('Ada');
```

Kontroller sorğuları emal etmək üçün istifadə olunan sinifdir. Xidmət təkrar istifadə edilə bilən məsuliyyəti olan sinifdir. Məsuliyyətin adlandırılması layihə böyüdükcə kodu anlamağı asanlaşdırır.

## Konstruktorlar və asılılıqların yeridilməsi

Konstruktor obyektə lazım olan asılılıqları qəbul edir:

```php
final class WelcomeController
{
    public function __construct(private Greeter $greeter) {}
}
```

Qısa `private Greeter $greeter` sintaksisi xüsusiyyəti təyin edir və saxlayır. Elang konteyneri Greeter yarada və onu kontrollerə ötürə bilər. Bu, asılılıqların yeridilməsidir; qlobal fasad və ya gizli dəyişən tələb etmir.

## Ad məkanları və use ifadələri

Tam sinif adına onun ad məkanı daxildir:

```php
namespace App\Controllers;
use Elang\Http\Response;
```

`use` faylda mövcud sinfə daha qısa adla müraciət etməyə imkan verir. O, həmin sinfi endirmir və ya quraşdırmır. Composer-in avtomatik yükləyicisi layihənin PSR-4 uyğunlaşdırmasından istifadə edərək sinif faylını tapır.

Yaradılmış tətbiqdə `App\Controllers\HelloController` sinfi `app/Controllers/HelloController.php` faylında yerləşməlidir. Xüsusilə Linux-da yazılışı və böyük-kiçik hərfləri uyğun saxlayın.

## Statik çağırışlar və sinif adları

`Response::json(...)` statik köməkçini çağırır. `HelloController::class` tam sinif adını sətir kimi yaradır ki, marşrutlaşdırıcı sonradan onu konteynerdən istəyə bilsin. Bu, kontrolleri dərhal icra etmir.

## İstisnalar

İstisna normal icranı kəsir. Elang dəstəklənən HTTP və yoxlama istisnalarını cavablara çevirir. Gözlənilməyən istisnalar jurnala yazılır və istehsal mühitində təhlükəsiz 500 cavabına çevrilir.

Bütün istisnaları tutub sorğu uğurlu olmuş kimi davranmayın. Gözlənilən statusdan istifadə edin, xətaları yoxlayın və şəxsi təfərrüatları ictimai çıxışdan kənarda saxlayın.

## Qısa özünü yoxlama

Marşrutun niyə massiv qaytardığını, `$name` dəyişəninin niyə dollar işarəsi ilə başladığını, `->` və `::` işarələrinin nə demək olduğunu və use ifadəsinin niyə paket quraşdırmadığını izah edə bilərsiniz? Edə bilirsinizsə, [ilk marşrutunuz](/learn/first-route) ilə davam edin. Termin tanış deyilsə, redaktorunuzun yanında [terminlər lüğətini](/docs/v0.1/glossary) açıq saxlayın.

Dil üzrə daha geniş istinad mənbəyi üçün [rəsmi PHP dərsliyini](https://www.php.net/manual/en/tutorial.php) oxuyun.
