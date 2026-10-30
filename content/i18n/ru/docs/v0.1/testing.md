# Тестирование

Репозиторий фреймворка содержит тесты PHPUnit для поведения HTTP, контейнера, middleware, валидации, безопасности, операций с базой данных и маршрутов документации.

## Запуск набора тестов

```bash
composer test
composer analyse
composer audit
```

Для тестов и статического анализа фреймворку нужны зависимости разработки. Устанавливайте зависимости рабочей среды командой `composer install --no-dev` только в отдельный артефакт для развёртывания.

## HTTP-запрос в тесте

```php
use Elang\Application;
use Symfony\Component\HttpFoundation\Request;

$app = new Application($projectPath, ['debug' => false]);
$app->router->get('/hello', fn () => ['message' => 'Hello']);
$response = $app->handle(Request::create('/hello'));

self::assertSame(200, $response->getStatusCode());
self::assertSame('Hello', json_decode($response->getContent(), true)['message']);
```

Используйте временный каталог проекта для изолированного хранилища. Проверяйте авторизацию, небезопасные входные данные, ответы при сбоях и поведение базы данных в собственных приложениях.

## Совместимость

Рабочий процесс CI рассчитан на PHP 8.3 и 8.4. Успешное прохождение набора проверяет поведение, охваченное тестами; оно не является аудитом безопасности или доказательством превосходства в производительности над другим фреймворком.


## Следующий шаг

[Тестирование приложения](/ru/docs/v0.1/application-testing).
