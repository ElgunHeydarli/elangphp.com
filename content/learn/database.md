# Connect a database

Use SQLite for your first database-backed feature. Ensure `pdo_sqlite` is installed.

## Create the schema

Run `php elang make:migration create_posts`. Add a `posts` table in `up()` using Doctrine's Table API, with an integer primary key and a string title. See the complete schema example in the migrations documentation.

Run `php elang migrate`, then insert a record through DBAL in your application code or a local setup script:

```php
$database->connection()->insert('posts', ['id' => 1, 'title' => 'Hello from SQLite']);
```

## Read records

```php
use Elang\Database\Database;
$router->get('/api/posts', function (Database $database) {
    return ['data' => $database->table('posts')
        ->orderBy('id', 'DESC')
        ->setMaxResults(20)
        ->fetchAllAssociative()];
});
```

## Bind values

When filtering, write `->where('id = :id')->setParameter('id', $id)`. Do not interpolate request values into SQL fragments or identifiers.

## Verify the result

Open `/api/posts` and confirm your record is returned. Keep SQLite files outside the public document root and out of version control.
