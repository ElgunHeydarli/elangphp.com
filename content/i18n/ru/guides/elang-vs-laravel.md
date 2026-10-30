# Elang, Laravel, CodeIgniter и Symfony: выбор по фактам

Сравнение описывает development preview на 30 октября 2026 года. Elang — самостоятельный PHP framework на Symfony components и Doctrine, а не дистрибутив Laravel и не реализация его API.

Laravel документирует Eloquent models, relationships и collections. CodeIgniter описывает Model CRUD, validation, pagination, timestamps и soft deletion. Symfony предоставляет повторно используемые HttpFoundation, Console, Cache и Clock. У проектов свои соглашения и экосистемы; одинаковое название возможности не означает совместимость исходного кода.

[Laravel Eloquent](https://laravel.com/framework/docs/13.x/eloquent), [CodeIgniter Model](https://codeigniter.com/user_guide/models/model.html), [Symfony components](https://symfony.com/doc/current/components/index.html), [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/getting-started.html).

## Возможности, доступные в Elang

| Область | Реализованное поведение | Изучить |
| --- | --- | --- |
| HTTP и сервисы | Routes, middleware, PSR-11 DI, Symfony validation | [routing](/ru/docs/v0.1/routing) |
| Аккаунты | Подтверждение, roles/policies, scoped tokens, MFA/passkeys | [account-security](/ru/docs/v0.1/account-security) |
| База | DBAL repositories и необязательная Doctrine ORM | [orm](/ru/docs/v0.1/orm) |
| Преобразования | Неизменяемые коллекции и ленивые потоки | [collections](/ru/docs/v0.1/collections) |
| JSON | Явные публичные поля и meta основного paginator | [api-resources](/ru/docs/v0.1/api-resources) |
| Партнёрские API | TLS, предел ответа, scoped credentials, strict fakes | [http-client](/ru/docs/v0.1/http-client) |
| Тесты | Kernel requests, sessions, CSRF, mail в памяти и HTTP assertions | [application-testing](/ru/docs/v0.1/application-testing) |
| Команды | Расширения Symfony Console без скрытой замены команд | [custom-console](/ru/docs/v0.1/custom-console) |
| Фоновые задачи | Очередь/расписание в базе, SSE, защищённые WebSockets | [queues](/ru/docs/v0.1/queues) |
| Основа приложений | Redis, teams, private storage, notifications, telemetry, starters | [fullstack-starters](/ru/docs/v0.1/fullstack-starters) |

## Выберите слой данных

Для небольших прямых операций оставьте DBAL repositories. Используйте Doctrine ORM, когда домену подходят связи, unit of work и identity map. Они делят DBAL-соединение, но основные Model и Doctrine entities — разные модели; их factories и адаптеры пагинации не взаимозаменяемы автоматически.

## Оставшаяся работа

Дополнительные queue brokers, семантический поиск документации, интеграции редакторов и автоматические upgrade tools остаются в планах. Стабильному выпуску нужны стабилизация API, политика поддержки, независимая проверка безопасности и более широкие данные реальных приложений/нагрузки. Billing providers, search engines и OAuth vendors оцениваются отдельно: наличие всех сторонних пакетов других экосистем не заявляется.

## Проверьте на реальном проекте

Реализуйте одинаковые входы, правила доступа, операции данных и тесты в каждом варианте. Зафиксируйте время настройки, сопровождение и ошибки. Для сравнения скорости нужны PHP/SAPI, OPcache, оборудование, зависимости, payload, прогрев, параллельность и воспроизводимые сырые результаты. Универсальное превосходство в скорости, безопасности или production capacity здесь не установлено.

Начните с task-board, затем перейдите к API партнёра, entities/resources и тестированию приложения. Во всех языках исполняемый код одинаков. Каталог проверяется suite на временной базе и fake API.

[Learn](/ru/learn) · [ORM](/ru/docs/v0.1/orm) · [Application testing](/ru/docs/v0.1/application-testing)
