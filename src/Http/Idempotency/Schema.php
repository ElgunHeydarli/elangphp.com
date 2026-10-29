<?php
declare(strict_types=1);
namespace Elang\Http\Idempotency;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $connection):void
    {
        $schema=$connection->createSchemaManager();if($schema->tablesExist(['elang_idempotency'])) return;
        $table=new Table('elang_idempotency');$table->addColumn('key_hash','string',['length'=>64]);$table->addColumn('request_hash','string',['length'=>64]);$table->addColumn('response_body','text');$table->addColumn('response_status','integer');$table->addColumn('response_headers','text');$table->addColumn('created_at','bigint');$table->setPrimaryKey(['key_hash']);$table->addIndex(['created_at']);$schema->createTable($table);
    }
}
