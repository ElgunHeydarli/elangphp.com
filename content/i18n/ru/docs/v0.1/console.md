# Консольные команды

Выполните `php elang`, чтобы увидеть доступные команды. Команды реализованы с помощью Symfony Console.

## Повседневные команды

```bash
php elang doctor
php elang about
php elang routes
php elang serve --port=8000
php elang make:controller WelcomeController
php elang make:migration create_posts
php elang migrate:status
php elang migrate
php elang cache:clear
```

Сервер разработки по умолчанию привязывается к 127.0.0.1. Для развёртывания используйте промышленный веб-сервер и PHP-FPM.

## Настройка приложения

```bash
php elang key:generate
php elang new ../my-app --local
```

Генераторы отклоняют непустые каталоги назначения и существующие файлы контроллеров. Имена контроллеров используют PascalCase, имена миграций — snake_case.

## Миграции в рабочей среде

Команды, изменяющие базу данных, требуют `--force` при `APP_ENV=production`. Замена ключа также требует `--force`; она делает прежние зашифрованные данные недействительными. Храните секреты рабочей среды и резервные копии вне системы контроля версий.

## Проверки фреймворка

```bash
composer test
composer analyse
composer audit
```

Эти команды выполняются в репозитории фреймворка. При отправке рабочего процесса в репозиторий GitHub Actions запускает проверки совместимости PHP, валидацию зависимостей, тесты и статический анализ.

## Создание проектов глобальным установщиком

После [однократной настройки CLI](/docs/v0.1/installation) выполните `elang new my-app` или `elang new task-board --example=task-board`. Глобальная команда делегирует установку зависимостей и настройку проекта Composer. Внутри проекта используйте `php elang` для запуска собственной консоли приложения.


## Следующий шаг

[Консольные команды приложения](/ru/docs/v0.1/custom-console).
