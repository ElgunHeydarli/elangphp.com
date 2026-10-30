# Xarici API-lər üçün HTTP müştərisi

Partnyor API-ləri üçün konteynerdən verilən `Elang\Http\Client\Client` istifadə edin. İlk nümunə xarici xidmət olmadan işləyir: cavabı fake transport qaytarır.

## İşlək nümunə


```php
use Elang\Http\Client\{Client, FakeTransport, Response};

$fake = (new FakeTransport())->when(
    'https://partner.example.test/stock*',
    new Response(200, '{"data":{"quantity":12}}'),
);
$http = new Client($fake);
$quantity = $http->baseUrl('https://partner.example.test')
    ->timeout(10)->retry(3, 100)
    ->get('/stock', ['sku' => 'A-100'])
    ->throw()->json('data.quantity'); // 12
$fake->assertSentCount(1);
```

Real inteqrasiyada fake yaratmaq əvəzinə kontrollerə Client verin. Baza URL-ni server konfiqurasiyasında saxlayın, məxfi açarı `Config::secret()` ilə oxuyun. withToken və withHeaders həmin inteqrasiya üçün ayrıca sorğu konfiqurasiyası qaytarır; ortaq Client başqa sorğulara bu açarı daşımır. Baza URL-dən başqa origin-ə keçid rədd edilir.

Standartlar: TLS yoxlaması aktivdir, yönləndirmə və cookie deaktivdir; sorğu/oxuma limiti 10 saniyə, qoşulma limiti 5 saniyə, cavab həddi 8 MiB-dir. Limitləri iş yükünə uyğunlaşdırın. Stream və cURL handler qoşulma limitini fərqli tətbiq edə bilər. `throw()` 4xx/5xx üçün yalnız status göstərən RequestException yaradır; JSON gözləyəndə `json()` çağırın.

`retry(3, 100)` cəmi üç cəhd və 100 ms fasilə deməkdir. Standartda yalnız GET, HEAD və OPTIONS qoşulma xətası və 429/502/503/504 zamanı təkrarlanır. Yazını təkrarlamaq üçün `retryUnsafe: true` və tətbiq səviyyəsində idempotentlik lazımdır. Retry-After on saniyədən böyükdürsə erkən təkrar əvəzinə cavab qaytarılır. Fake üçün gözlənilməyən sorğu şəbəkəyə çıxmadan xəta verir. İstifadəçi girişindən yoxlanılmamış server URL-i yaratmayın.

## Birlikdə yoxlayın

ORM quraşdırılmış tətbiqdə tam kataloq nümunəsini işlədin. Müvəqqəti yaddaş bazası və fake partnyor API istifadə olunur. Nəticədə `"checks":"passed"` görünməlidir. Nümunə entity əlaqələri, fetch join, resurs, kolleksiya və HTTP/kernel yoxlamalarını birləşdirir.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Növbəti addım](/az/docs/v0.1/application-testing) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Tez-tez edilən səhvlər

| Əlamət | Düzəliş |
| --- | --- |
| Gözlənilməyən fake sorğu | Tam URL-ni və ya query parametrlərini nəzərə alan wildcard qeydiyyatdan keçirin. |
| 429 və uzun Retry-After | Tapşırığı sonrakı vaxta planlaşdırın; tələb olunan fasiləni qısaltmayın. |
