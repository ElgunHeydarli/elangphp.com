# Создание PHP REST API на Elang

Это руководство создаёт небольшой API каталога с доступом только для чтения и отдельный эндпоинт валидации ввода. Вы будете возвращать JSON, принимать параметр маршрута, использовать коды статуса HTTP и отклонять некорректный ввод. Каталог намеренно хранится в памяти, чтобы вы могли понять поведение HTTP до добавления постоянного хранения в базе данных.

## Создание приложения

Выполните команды в родительском каталоге нового приложения:

```bash
composer create-project elang/elang catalog-api dev-main
cd catalog-api
php elang doctor
```

Оставьте терминал в каталоге нового приложения. Замените `routes/web.php` полным файлом, приведённым ниже.

## Регистрация эндпоинтов

```php
<?php
declare(strict_types=1);

use Elang\Http\HttpException;
use Elang\Http\Middleware\RateLimitMiddleware;
use Elang\Validation\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

$catalog = [
    1 => ['id' => 1, 'title' => 'Learn routing'],
    2 => ['id' => 2, 'title' => 'Validate input'],
];

$router->group('/api', [RateLimitMiddleware::class], function ($router) use ($catalog) {
    $router->get('/lessons', fn () => ['data' => array_values($catalog)]);

    $router->get('/lessons/{id}', function (int $id) use ($catalog) {
        if (!isset($catalog[$id])) {
            throw new HttpException(404, 'This lesson does not exist.');
        }
        return ['data' => $catalog[$id]];
    })->where('id', '[0-9]+');

    $router->post('/validate-contact', function (Request $request, Validator $validator) {
        if (!str_contains($request->headers->get('Content-Type', ''), 'application/json')) {
            throw new HttpException(415, 'Send an application/json request.');
        }
        try {
            $data = json_decode($request->getContent(), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException(400, 'Invalid JSON body.');
        }
        if (!is_array($data) || array_is_list($data) && $data !== []) {
            throw new HttpException(400, 'Expected a JSON object.');
        }
        $valid = $validator->validate($data, [
            'name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 80)],
            'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()],
        ]);
        return ['message' => 'Input accepted; nothing was stored.', 'data' => $valid];
    });
});
```

Все значения и маршруты определены в файле. Никаких пропущенных шагов с контроллерами или базой данных нет. Эндпоинт валидации не создаёт учётную запись, не отправляет почту и не сохраняет запись.

## Запуск сервера

```bash
php elang serve
```

Откройте второй терминал для запросов, оставив сервер работающим в первом. В Windows PowerShell используйте `curl.exe` для исполняемого файла cURL или HTTP-клиент, позволяющий задать метод, заголовки и тело JSON.

## Чтение коллекции и элемента

```bash
curl http://127.0.0.1:8000/api/lessons
curl http://127.0.0.1:8000/api/lessons/1
```

Первый ответ содержит оба объекта уроков в `data`. Второй содержит урок с ID 1. Запрос `/api/lessons/999` возвращает 404 с сообщением и идентификатором запроса. Нечисловой идентификатор не соответствует требованию маршрута.

## Отправка корректного JSON

В оболочке POSIX выполните:

```bash
curl -X POST http://127.0.0.1:8000/api/validate-contact \
  -H 'Content-Type: application/json' \
  -d '{"name":"Ada","email":"ada@example.com"}'
```

Либо используйте HTTP-клиент: выберите POST, введите URL, добавьте `Content-Type: application/json` и вставьте JSON-объект в необработанное тело запроса. Ожидаемый статус: 200. Ответ повторяет только проверенные поля и объясняет, что ничего не было сохранено.

## Проверьте сценарии ошибок

| Запрос | Ожидаемый результат |
| --- | --- |
| Некорректный JSON | 400 с безопасным сообщением |
| Отсутствующий Content-Type для JSON | 415 |
| Неверный адрес электронной почты | 422 с ошибкой поля email |
| POST на маршрут коллекции GET | 405 с Allow |
| Неизвестный ID урока | 404 |
| Запросы сверх настроенного ограничения | 429 с Retry-After |

Используйте эти сценарии как начало автоматизированного набора тестов API. Один успешный запрос не показывает, правильно ли работает граница входных данных.

## Добавляйте постоянное хранение осознанно

Следующий шаг — [доступ к базе данных](/docs/v0.1/database), затем [миграции](/docs/v0.1/migrations). Привязывайте все пользовательские значения как параметры запросов. Решите, кто может создавать или изменять запись, прежде чем добавлять эндпоинты записи в публичное приложение.

В этом примере нет системы идентификации или авторизации, поскольку он только предоставляет каталог, контролируемый приложением, и проверяет данные без их сохранения. Добавление аутентификации, проверок принадлежности и долговременной записи — отдельная задача проектирования.

## Продолжение обучения

Пройдите [полный проект доски задач](/learn/build-a-task-board), чтобы создать HTML-приложение с формами, изменяющими состояние, хранением в сессии, защитой CSRF и обратной связью валидации. Используйте [справочник HTTP-ответов](/docs/v0.1/controllers), когда нужны собственные статусы или заголовки.
