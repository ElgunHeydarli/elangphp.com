# Миграции

Миграции фиксируют изменения схемы базы данных. Каждый файл возвращает объект, реализующий интерфейс Migration.

## Создание миграции

```bash
php elang make:migration create_posts
```

Измените созданный файл в `database/migrations/`:

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

## Применение и проверка

```bash
php elang migrate:status
php elang migrate
```

Миграции выполняются в порядке имён файлов; уже применённые файлы пропускаются. Таблица миграций отслеживает версии и пакеты применения. Не изменяйте применённую миграцию; создайте новую.

## Откат

```bash
php elang migrate:rollback
```

Команда вызывает `down()` для последнего пакета миграций в обратном порядке. Откат может удалить данные. Сначала создайте резервную копию важных данных и проверьте код. Для изменений в рабочей среде требуется `--force`.

Миграции SQLite и PostgreSQL выполняются в транзакциях. DDL в MySQL может неявно фиксировать транзакцию, поэтому изменения схемы требуют продуманного восстановления после сбоев. Одновременные запуски миграций на одном хосте используют файловую блокировку; при развёртывании на нескольких хостах запускайте миграции из одного задания развёртывания.
