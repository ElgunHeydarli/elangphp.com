# Integrate a partner API safely

Prerequisite: routes, JSON and validation. Goal: read stock with a bounded timeout and verify the response without contacting the partner.

Use the HTTP example below, then inject Client in the real controller. Keep a fixed server-configured URL and a private token. Try a 503 followed by 200 with FakeTransport::sequence; confirm that GET retries while POST does not retry by default.

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

You are done when the quantity is 12, exactly one fake request is recorded, and an unregistered fake URL fails locally. Read the HTTP client chapter before enabling a real integration.

[Next step](/docs/v0.1/http-client)
