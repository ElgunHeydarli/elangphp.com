<?php
declare(strict_types=1);
namespace Elang\Notification;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $db):void
    {
        if(!$db->createSchemaManager()->tablesExist(['elang_notifications'])) { $t=new Table('elang_notifications');$t->addColumn('id','string',['length'=>32]);$t->addColumn('user_id','integer');$t->addColumn('subject','string',['length'=>160]);$t->addColumn('body','text');$t->addColumn('url','string',['length'=>500,'notnull'=>false]);$t->addColumn('created_at','bigint');$t->addColumn('read_at','bigint',['notnull'=>false]);$t->setPrimaryKey(['id']);$t->addIndex(['user_id','created_at']);$t->addForeignKeyConstraint('auth_users',['user_id'],['id'],['onDelete'=>'CASCADE']);$db->createSchemaManager()->createTable($t); }
    }
}
