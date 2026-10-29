# Подключение базы данных

Для первой возможности, использующей базу данных, возьмите SQLite. Убедитесь, что установлен `pdo_sqlite`.

## Создание схемы

Выполните `php elang make:migration create_posts`. Добавьте таблицу `posts` в `up()` с помощью API Table Doctrine: с целочисленным первичным ключом и строковым заголовком. Полный пример схемы приведён в документации миграций.

Выполните `php elang migrate`, затем вставьте запись через DBAL в коде приложения или локальном скрипте настройки:

```php
$database->connection()->insert('posts', ['id' => 1, 'title' => 'Hello from SQLite']);
```

## Чтение записей

```php
use Elang\Database\Database;
$router->get('/api/posts', function (Database $database) {
    return ['data' => $database->table('posts')
        ->orderBy('id', 'DESC')
        ->setMaxResults(20)
        ->fetchAllAssociative()];
});
```

## Привязка значений

При фильтрации используйте `->where('id = :id')->setParameter('id', $id)`. Не подставляйте значения запроса в SQL-фрагменты или идентификаторы.

## Проверка результата

Откройте `/api/posts` и убедитесь, что запись возвращается. Храните файлы SQLite вне публичного корневого веб-каталога и системы контроля версий.

## Полная первая миграция

Если это первое изменение схемы, замените тело сгенерированной миграции следующей полной реализацией. Сохраните сгенерированное имя файла.

```php
<?php
declare(strict_types=1);
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Elang\Database\Migration;
return new class implements Migration {
    public function up(Connection $database): void
    {
        $table = new Table('posts');
        $table->addColumn('id', 'integer');
        $table->addColumn('title', 'string', ['length' => 160]);
        $table->setPrimaryKey(['id']);
        $database->createSchemaManager()->createTable($table);
        $database->insert('posts', ['id' => 1, 'title' => 'Hello from SQLite']);
    }
    public function down(Connection $database): void
    {
        $database->createSchemaManager()->dropTable('posts');
    }
};
```

Выполните `php elang migrate`, затем используйте приведённый выше маршрут GET. Ожидаемый ответ:

```json
{"data":[{"id":1,"title":"Hello from SQLite"}]}
```

Миграция содержит начальную запись, поэтому после применения этой полной миграции не выполняйте ещё и предыдущий фрагмент вставки. Тот фрагмент демонстрирует отдельную операцию записи; повторная вставка того же первичного ключа закономерно завершится ошибкой.

Если сервер сообщает «no such table» («таблица не найдена»), сравните DATABASE_URL приложения с адресом, используемым командой миграции. Если он сообщает «could not find driver» («драйвер не найден»), проверьте `php -m` и включите pdo_sqlite. См. [устранение проблем с базой данных](/docs/v0.1/troubleshooting).
