<?php
declare(strict_types=1);
namespace Elang\Storage;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $db):void
    {
        if($db->createSchemaManager()->tablesExist(['elang_files'])) return;$t=new Table('elang_files');$t->addColumn('id','string',['length'=>32]);$t->addColumn('user_id','integer');$t->addColumn('team_id','string',['length'=>32,'notnull'=>false]);$t->addColumn('disk','string',['length'=>10]);$t->addColumn('object_key','string',['length'=>100]);$t->addColumn('name','string',['length'=>180]);$t->addColumn('mime','string',['length'=>100]);$t->addColumn('bytes','bigint');$t->addColumn('sha256','string',['length'=>64]);$t->addColumn('created_at','bigint');$t->setPrimaryKey(['id']);$t->addIndex(['team_id','created_at']);$t->addIndex(['user_id','created_at']);$t->addForeignKeyConstraint('auth_users',['user_id'],['id']);$db->createSchemaManager()->createTable($t);
    }
}
