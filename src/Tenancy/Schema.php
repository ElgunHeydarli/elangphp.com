<?php
declare(strict_types=1);
namespace Elang\Tenancy;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $db):void
    {
        $manager=$db->createSchemaManager();
        if(!$manager->tablesExist(['elang_teams'])) { $t=new Table('elang_teams');$t->addColumn('id','string',['length'=>32]);$t->addColumn('name','string',['length'=>80]);$t->addColumn('created_at','bigint');$t->setPrimaryKey(['id']);$manager->createTable($t); }
        if(!$manager->tablesExist(['elang_members'])) { $t=new Table('elang_members');$t->addColumn('team_id','string',['length'=>32]);$t->addColumn('user_id','integer');$t->addColumn('role','string',['length'=>10]);$t->addColumn('created_at','bigint');$t->setPrimaryKey(['team_id','user_id']);$t->addForeignKeyConstraint('elang_teams',['team_id'],['id'],['onDelete'=>'CASCADE']);$t->addForeignKeyConstraint('auth_users',['user_id'],['id'],['onDelete'=>'CASCADE']);$manager->createTable($t); }
        if(!$manager->tablesExist(['elang_invites'])) { $t=new Table('elang_invites');$t->addColumn('token_hash','string',['length'=>64]);$t->addColumn('team_id','string',['length'=>32]);$t->addColumn('email','string',['length'=>190]);$t->addColumn('role','string',['length'=>10]);$t->addColumn('expires_at','bigint');$t->setPrimaryKey(['token_hash']);$t->addForeignKeyConstraint('elang_teams',['team_id'],['id'],['onDelete'=>'CASCADE']);$t->addUniqueIndex(['team_id','email']);$manager->createTable($t); }
    }
    public static function down(Connection $db):void { foreach(['elang_invites','elang_members','elang_teams'] as $name) if($db->createSchemaManager()->tablesExist([$name])) $db->createSchemaManager()->dropTable($name); }
}
