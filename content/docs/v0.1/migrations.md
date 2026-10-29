# Migrations

Migrations record changes to your database schema. Each file returns an object implementing the Migration interface.

## Generate a migration

```bash
php elang make:migration create_posts
```

Edit the generated file in `database/migrations/`:

```php
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
    }

    public function down(Connection $database): void
    {
        $database->createSchemaManager()->dropTable('posts');
    }
};
```

## Apply and inspect

```bash
php elang migrate:status
php elang migrate
```

Migrations run in filename order, and already applied files are skipped. The migration table tracks versions and batches. Do not edit an applied migration; write a new one.

## Roll back

```bash
php elang migrate:rollback
```

This calls `down()` for the latest batch in reverse order. A rollback can delete data. Back up important data and review the code first. Production changes require `--force`.

SQLite and PostgreSQL migrations are transactional. MySQL DDL may implicitly commit, so schema changes there need careful failure recovery. Concurrent migration runs on one host use a file lock; multi-host deployments should run migrations from one deployment job.
