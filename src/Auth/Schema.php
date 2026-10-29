<?php
declare(strict_types=1);
namespace Elang\Auth;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
final class Schema
{
    public static function up(Connection $database): void
    {
        $schema = $database->createSchemaManager();
        if (!$schema->tablesExist(['auth_users'])) {
            $users = new Table('auth_users');
            $users->addColumn('id','integer',['autoincrement'=>true]);
            $users->addColumn('name','string',['length'=>80]); $users->addColumn('email','string',['length'=>190]);
            $users->addColumn('password','string',['length'=>255]); $users->addColumn('role','string',['length'=>20,'default'=>'user']);
            $users->addColumn('verified_at','bigint',['notnull'=>false]); $users->addColumn('auth_version','integer',['default'=>1]);
            $users->addColumn('must_change_password','boolean',['default'=>false]); $users->addColumn('created_at','bigint');
            $users->setPrimaryKey(['id']); $users->addUniqueIndex(['email'],'auth_users_email_unique'); $schema->createTable($users);
        }
        if (!$schema->tablesExist(['auth_tokens'])) {
            $tokens = new Table('auth_tokens'); $tokens->addColumn('token_hash','string',['length'=>64]);
            $tokens->addColumn('user_id','integer'); $tokens->addColumn('purpose','string',['length'=>20]);
            $tokens->addColumn('expires_at','bigint'); $tokens->setPrimaryKey(['token_hash']);
            $tokens->addIndex(['user_id','purpose']); $tokens->addForeignKeyConstraint('auth_users',['user_id'],['id'],['onDelete'=>'CASCADE']); $schema->createTable($tokens);
        }
        if (!$schema->tablesExist(['auth_api_tokens'])) {
            $tokens = new Table('auth_api_tokens'); $tokens->addColumn('id','integer',['autoincrement'=>true]);
            $tokens->addColumn('user_id','integer'); $tokens->addColumn('name','string',['length'=>80]);
            $tokens->addColumn('token_hash','string',['length'=>64]); $tokens->addColumn('scopes','text');
            $tokens->addColumn('expires_at','bigint'); $tokens->addColumn('created_at','bigint');
            $tokens->setPrimaryKey(['id']); $tokens->addUniqueIndex(['token_hash']); $tokens->addForeignKeyConstraint('auth_users',['user_id'],['id'],['onDelete'=>'CASCADE']); $schema->createTable($tokens);
        }
    }
    public static function down(Connection $database): void
    {
        $schema=$database->createSchemaManager(); foreach(['auth_api_tokens','auth_tokens','auth_users'] as $name) if($schema->tablesExist([$name])) $schema->dropTable($name);
    }
}
