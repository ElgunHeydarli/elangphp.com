# Структура каталогов

В приложении Elang код и содержимое размещаются в небольшом числе явно определённых каталогов.

## Файлы приложения

```text
app/                 Your controllers and services
bootstrap/app.php    Application creation and route loading
config/app.php       Configuration array
routes/web.php       Route definitions
resources/views/     Twig HTML templates
public/              Web document root; CSS and JavaScript
database/migrations/ Versioned database changes
storage/             Writable cache, logs, sessions, and locks
elang                Console entry point
```

## Исходный код фреймворка

В репозитории фреймворка `src/` содержит пространство имён `Elang`. Приложение публичного сайта находится в `app/`, а документация, уроки и примеры — в `content/`.

## Жизненный цикл запроса

Публичная точка входа загружает Composer, создаёт приложение и вызывает `run()`. Сначала выполняются глобальные middleware, затем сопоставление маршрута, middleware маршрута и обработчик. Ответы подготавливаются с учётом запроса; ошибки получают идентификаторы запросов и безопасные заголовки по умолчанию.

Контроллеры возвращают HTTP-ответ, массив для JSON, строку для HTML или `null` для ответа 204.
