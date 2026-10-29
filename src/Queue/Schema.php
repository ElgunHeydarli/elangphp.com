<?php
declare(strict_types=1);
namespace Elang\Queue;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $connection):void
    {
        $schema=$connection->createSchemaManager();
        if(!$schema->tablesExist(['elang_jobs'])) {
            $table=new Table('elang_jobs');$table->addColumn('id','string',['length'=>32]);$table->addColumn('name','string',['length'=>120]);$table->addColumn('payload','text');$table->addColumn('state','string',['length'=>16]);$table->addColumn('attempts','integer');
            foreach(['available_at','created_at'] as $column) $table->addColumn($column,'bigint');
            $table->addColumn('reserved_until','bigint',['notnull'=>false]);$table->addColumn('reservation','string',['length'=>32,'notnull'=>false]);$table->addColumn('last_error','string',['length'=>200,'notnull'=>false]);$table->setPrimaryKey(['id']);$table->addIndex(['state','available_at']);$table->addIndex(['state','reserved_until']);$schema->createTable($table);
        }
        if(!$schema->tablesExist(['elang_schedule_runs'])) {
            $table=new Table('elang_schedule_runs');$table->addColumn('run_key','string',['length'=>64]);$table->addColumn('created_at','bigint');$table->setPrimaryKey(['run_key']);$schema->createTable($table);
        }
    }
}
