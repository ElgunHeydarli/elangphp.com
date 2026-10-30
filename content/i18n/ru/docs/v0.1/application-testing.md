# Тестирование приложения

TestClient выполняет запросы через реальный kernel и middleware с отдельной сессией в памяти для каждого клиента. Он требует явно указанную среду testing. Создайте изолированную базу и storage; клиент не копирует и не заменяет вашу базу автоматически.

## Рабочий пример
Установите phpunit/phpunit:^12 как dev dependency. Сохраните полный пример ниже в tests/ApiTest.php; запустите vendor/bin/phpunit --bootstrap vendor/autoload.php tests. В фабрике тестового приложения подключайте настоящие routes и providers к изолированному приложению, чтобы проверять поведение своего проекта.

```bash
composer require --dev phpunit/phpunit:^12
```



```php
<?php
declare(strict_types=1);
use Elang\Application;
use Elang\Testing\TestClient;
use PHPUnit\Framework\TestCase;

final class ApiTest extends TestCase
{
    public function testReadyEndpoint(): void
    {
        $directory = sys_get_temp_dir().'/my-elang-test-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700, true);
        try {
            $app = new Application($directory, [
                'environment' => 'testing',
                'database' => ['url' => 'sqlite:///:memory:'],
                'mail' => ['dsn' => 'null://null'],
                'redis' => ['url' => ''],
                'telemetry' => ['enabled' => false],
                'operations' => ['enabled' => false],
            ]);
            $app->router->get('/api/ready', fn () => ['ready' => true]);
            $browser = new TestClient($app);
            $browser->get('/api/ready')->assertOk()->assertJsonPath('ready', true);
        } finally {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                $file->isDir() && !$file->isLink()
                    ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($directory);
        }
    }
}
```

## Проверки и пользователи

Есть проверки статуса, headers, redirects, текста, JSON paths/counts и параметризованных запросов к базе. withSession задаёт сессию, actingAs($persistedUser) выполняет вход. Сессии клиентов разделены даже в одном приложении.

## CSRF остаётся включённым

CSRF остаётся включённым. Защищённая запись без токена должна возвращать 419. Для корректного запроса используйте withCsrf()->post: он передаёт настоящий токен и same-origin данные, не отключая middleware. Проверки scopes и владельца сохраняются.

## Fakes для HTTP и почты

Клиент подменяет внедряемый Elang HTTP Client строгим FakeTransport, а Symfony MailerInterface — recorder в памяти. Задайте http->when, проверьте запросы; доступны mail->messages(), assertSentCount и assertSentTo. Fakes общие для клиентов одного приложения. Прямой cURL, отдельно созданные mail transports и другие SDK не перехватываются: заменяйте их адаптеры явно. Никогда не направляйте тесты на live database.

## Проверьте вместе

Запустите полный пример каталога из приложения с установленной ORM. Он использует временную базу в памяти и fake API партнёра. В результате ожидается `"checks":"passed"`. Пример объединяет связи, fetch join, ресурсы, коллекции и HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Следующий шаг](/ru/docs/v0.1/deployment) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Частые ошибки

| Симптом | Исправление |
| --- | --- |
| Защищённая запись возвращает 419 | Проверьте отказ или используйте withCsrf() для корректной сессии. |
| Другой SDK выходит в сеть | Явно замените этот адаптер; подменяются только внедрённые Elang client и mailer. |
