<?php
declare(strict_types=1);
namespace Elang\Outbox;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $connection):void
    {
        $schema=$connection->createSchemaManager();if($schema->tablesExist(['elang_outbox'])) return;
        $table=new Table('elang_outbox');$table->addColumn('id','string',['length'=>32]);$table->addColumn('kind','string',['length'=>20]);$table->addColumn('payload','text');$table->addColumn('created_at','bigint');$table->addColumn('published_at','bigint',['notnull'=>false]);$table->setPrimaryKey(['id']);$table->addIndex(['published_at','created_at']);$schema->createTable($table);
    }
}
