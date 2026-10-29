# База данных

Elang использует Doctrine DBAL для подключений к базам данных и построения запросов. Это слой абстракции базы данных, а не ORM.

## Настройка подключения

По умолчанию используется SQLite с файлом `database/database.sqlite`. Для MySQL:

```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/my_app?charset=utf8mb4"
```

Для PostgreSQL используйте URL с `postgresql://` и установите `pdo_pgsql`. Кодируйте специальные символы в учётных данных в формате URL. Никогда не добавляйте секреты подключения в систему контроля версий.

## Запрос записей

```php
use Elang\Database\Database;

$rows = $database->table('posts')
    ->where('published = :published')
    ->setParameter('published', 1)
    ->orderBy('id', 'DESC')
    ->setMaxResults(10)
    ->fetchAllAssociative();
```

`Database::table()` проверяет идентификатор таблицы и возвращает построитель запросов Doctrine. Привязывайте пользовательские значения с помощью параметров. Имена столбцов, SQL-выражения и направления сортировки не становятся безопасными автоматически; выбирайте их из разрешённых списков, контролируемых приложением.

## Запись данных

```php
$database->connection()->insert('posts', [
    'title' => $validated['title'],
    'published' => 0,
]);
```

DBAL предоставляет операции вставки, обновления и удаления, подготовленные выражения и API для работы со схемой.

## Транзакции

```php
$database->transaction(function ($connection) {
    $connection->insert('posts', ['title' => 'Hello', 'published' => 0]);
});
```

Исключение откатывает транзакцию. Поведение DDL различается между базами данных; проверяйте операции миграции перед развёртыванием.
