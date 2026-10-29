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

## A complete first migration

If this is your first schema change, replace the generated migration's body with the following complete implementation. Preserve the generated filename.

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

Run `php elang migrate`, then use the GET route above. Expected response:

```json
{"data":[{"id":1,"title":"Hello from SQLite"}]}
```

The migration includes its seed record, so do not also run the earlier insert snippet after applying this complete migration. That earlier snippet demonstrates a standalone write; repeating the same primary key would correctly fail.

If the server reports “no such table,” compare the application's DATABASE_URL with the one used by the migration command. If it reports “could not find driver,” inspect `php -m` and enable pdo_sqlite. See [database troubleshooting](/docs/v0.1/troubleshooting).
