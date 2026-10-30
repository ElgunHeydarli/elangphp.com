# Безопасно подключите API партнёра

Требуются routes, JSON и validation. Цель: получить остаток с таймаутом и проверить ответ без обращения к партнёру.

Выполните пример, затем внедрите Client в настоящий контроллер. Используйте фиксированный URL из серверной конфигурации и приватный токен. Через FakeTransport::sequence верните 503, затем 200: GET должен повториться, POST по умолчанию — нет.

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

Задание выполнено, когда получено 12, зарегистрирован один fake-запрос, а незарегистрированный URL завершается локальной ошибкой. Перед реальным подключением прочитайте главу HTTP-клиента.

[Следующий шаг](/ru/docs/v0.1/http-client)
