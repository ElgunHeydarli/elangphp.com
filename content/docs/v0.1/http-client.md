# Outbound HTTP client

Use the injectable `Elang\Http\Client\Client` for partner APIs. The first example is runnable without an external service: its fake transport supplies the response.

## Working example


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

For a real integration, inject `Client` into a controller instead of constructing the fake. Configure the partner base URL in server configuration and read credentials with `Config::secret()`. `withToken()` and `withHeaders()` return a request scoped to that integration; the shared client does not acquire those credentials. A base URL cannot be overridden by another origin.

Defaults: TLS verification enabled, redirects and cookies disabled, 10-second request/read timeout, 5-second connection timeout, and an 8 MiB response limit. Configure timeouts and limits for the workload. Stream handlers may implement connection timeouts differently from cURL. `throw()` raises a status-only RequestException for 4xx/5xx; use `json()` explicitly when a JSON response is expected.

`retry(3, 100)` means at most three total attempts and a 100 ms delay. Only GET, HEAD and OPTIONS retry by default, for connection failures or 429/502/503/504. Unsafe writes require explicit `retryUnsafe: true` and application-level idempotency. A Retry-After longer than ten seconds returns the response instead of retrying too early. Unexpected fake requests always fail without network access. Never derive a partner URL from untrusted input; validate user input before making server-side requests.

## Try it together

Run the complete catalogue example from an application with the optional ORM installed. It uses a disposable in-memory database and a fake partner API. Expected output includes `"checks":"passed"`. The example covers entity relationships, fetch joins, resources, collections and HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Next step](/docs/v0.1/application-testing) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Common mistakes

| Symptom | Fix |
| --- | --- |
| Unexpected fake request | Register the full URL or an intentional wildcard including query parameters. |
| 429 with a long Retry-After | Schedule a later job; do not retry before the requested cooldown. |
