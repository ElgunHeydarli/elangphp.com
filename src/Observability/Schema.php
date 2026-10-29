<?php
declare(strict_types=1);
namespace Elang\Observability;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $db):void
    {
        $m=$db->createSchemaManager();if(!$m->tablesExist(['elang_http_metrics'])) { $t=new Table('elang_http_metrics');$t->addColumn('bucket','bigint');$t->addColumn('route_key','string',['length'=>64]);$t->addColumn('route_name','string',['length'=>180]);$t->addColumn('requests','bigint');$t->addColumn('errors','bigint');$t->addColumn('duration_ms','bigint');$t->addColumn('max_ms','bigint');$t->setPrimaryKey(['bucket','route_key']);$m->createTable($t); }
        if(!$m->tablesExist(['elang_workers'])) { $t=new Table('elang_workers');$t->addColumn('id','string',['length'=>32]);$t->addColumn('kind','string',['length'=>20]);$t->addColumn('last_seen','bigint');$t->addColumn('processed','integer');$t->setPrimaryKey(['id']);$m->createTable($t); }
    }
}
