<?php
declare(strict_types=1);
namespace Elang\Realtime;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $connection):void
    {
        $schema=$connection->createSchemaManager();if($schema->tablesExist(['elang_events'])) return;
        $table=new Table('elang_events');$table->addColumn('id','bigint',['autoincrement'=>true]);$table->addColumn('channel','string',['length'=>160]);$table->addColumn('event','string',['length'=>64]);$table->addColumn('payload','text');$table->addColumn('created_at','bigint');$table->setPrimaryKey(['id']);$table->addIndex(['channel','id']);$table->addIndex(['created_at']);$schema->createTable($table);
    }
}
