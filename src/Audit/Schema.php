<?php
declare(strict_types=1);
namespace Elang\Audit;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $db):void
    {
        $schema=$db->createSchemaManager();
        if(!$schema->tablesExist(['elang_audit_heads'])) { $table=new Table('elang_audit_heads');$table->addColumn('scope','string',['length'=>80]);$table->addColumn('last_hash','string',['length'=>64]);$table->addColumn('sequence','bigint',['default'=>0]);$table->setPrimaryKey(['scope']);$schema->createTable($table); }
        if(!$schema->tablesExist(['elang_audit'])) {
            $table=new Table('elang_audit');$table->addColumn('id','bigint',['autoincrement'=>true]);$table->addColumn('scope','string',['length'=>80]);$table->addColumn('sequence','bigint');$table->addColumn('actor_id','integer',['notnull'=>false]);$table->addColumn('action','string',['length'=>80]);$table->addColumn('metadata','text');$table->addColumn('created_at','bigint');$table->addColumn('previous_hash','string',['length'=>64]);$table->addColumn('entry_hash','string',['length'=>64]);$table->setPrimaryKey(['id']);$table->addUniqueIndex(['scope','sequence']);$table->addIndex(['created_at']);$schema->createTable($table);
        }
    }
}
