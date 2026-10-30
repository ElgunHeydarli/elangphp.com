# Partnyor API-ni təhlükəsiz qoşun

Ön şərt: route, JSON və validasiya. Məqsəd: vaxt limiti ilə stok oxumaq, partnyora müraciət etmədən cavabı yoxlamaq.

Nümunəni işlədin, sonra real kontrollerə Client verin. URL server ayarından, token məxfi konfiqurasiyadan gəlsin. FakeTransport::sequence ilə əvvəl 503, sonra 200 verin: GET təkrarlanmalı, POST standartda təkrarlanmamalıdır.

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

Miqdar 12 olduqda, bir fake sorğu qeydə alındıqda və qeydiyyatsız URL lokal xəta verdikdə tapşırıq hazırdır. Real inteqrasiyadan əvvəl HTTP müştərisi bölməsini oxuyun.

[Növbəti addım](/az/docs/v0.1/http-client)
