# HTTP-клиент для внешних API

Для партнёрских API используйте внедряемый Elang Http Client. Первый пример работает без внешнего сервиса: ответ возвращает fake transport.

## Рабочий пример


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

## Настройка партнёра

Для реального API внедрите Client в контроллер вместо fake. Базовый URL храните в серверной конфигурации, секрет читайте через Config::secret. withToken и withHeaders создают отдельную конфигурацию запроса; общий клиент не переносит эти данные в другие запросы. Переход с заданного базового URL на другой origin запрещён.

## Ограничения и ошибки

По умолчанию TLS проверяется, перенаправления и cookies отключены; таймаут запроса/чтения — 10 секунд, соединения — 5 секунд, предел ответа — 8 MiB. Настройте ограничения под нагрузку. Stream handler и cURL могут по-разному применять таймаут соединения. throw создаёт RequestException только со статусом для 4xx/5xx; JSON разбирайте явно через json.

## Повторы и fakes

retry(3, 100) означает максимум три попытки с паузой 100 мс. Автоматически повторяются только GET, HEAD и OPTIONS при ошибке соединения или 429/502/503/504. Для записи нужны явный retryUnsafe: true и идемпотентность приложения. Retry-After больше десяти секунд возвращает ответ без преждевременного повтора. Неожиданный fake-запрос падает без выхода в сеть. Не стройте URL серверного запроса из непроверенного пользовательского ввода.

## Проверьте вместе

Запустите полный пример каталога из приложения с установленной ORM. Он использует временную базу в памяти и fake API партнёра. В результате ожидается `"checks":"passed"`. Пример объединяет связи, fetch join, ресурсы, коллекции и HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Следующий шаг](/ru/docs/v0.1/application-testing) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Частые ошибки

| Симптом | Исправление |
| --- | --- |
| Неожиданный fake-запрос | Зарегистрируйте полный URL или осознанный wildcard с query parameters. |
| 429 с большим Retry-After | Запланируйте задачу позже; не сокращайте требуемую паузу. |
