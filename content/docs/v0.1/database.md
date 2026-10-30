# Database

Elang uses Doctrine DBAL for database connections and its query builder. This core database layer is separate from the optional Doctrine ORM integration.

## Configure a connection

SQLite is the default and uses `database/database.sqlite`. For MySQL:

```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/my_app?charset=utf8mb4"
```

For PostgreSQL use a `postgresql://` URL and install `pdo_pgsql`. URL-encode special characters in credentials. Never commit connection secrets.

## Query records

```php
use Elang\Database\Database;

$rows = $database->table('posts')
    ->where('published = :published')
    ->setParameter('published', 1)
    ->orderBy('id', 'DESC')
    ->setMaxResults(10)
    ->fetchAllAssociative();
```

`Database::table()` validates the table identifier and returns a Doctrine query builder. Bind user values using parameters. Column names, SQL expressions, and sort directions are not automatically safe; choose them from application-controlled allowlists.

## Writes

```php
$database->connection()->insert('posts', [
    'title' => $validated['title'],
    'published' => 0,
]);
```

DBAL provides insert, update, delete, prepared statements, and schema APIs.

## Transactions

```php
$database->transaction(function ($connection) {
    $connection->insert('posts', ['title' => 'Hello', 'published' => 0]);
});
```

An exception rolls back the transaction. Database-specific DDL behavior differs; review migration operations before deploying them.


## Next step

[Optional Doctrine ORM](/docs/v0.1/orm).
