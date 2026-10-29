<?php
declare(strict_types=1);
namespace Elang\Security\Account;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $db):void
    {
        $schema=$db->createSchemaManager();
        $definitions=[
            'auth_mfa'=>[['user_id','integer'],['secret','text'],['last_step','bigint',['default'=>-1]],['enabled','boolean',['default'=>false]],['created_at','bigint']],
            'auth_recovery_codes'=>[['code_hash','string',['length'=>64]],['user_id','integer']],
            'auth_devices'=>[['id_hash','string',['length'=>64]],['user_id','integer'],['auth_version','integer'],['agent','string',['length'=>180]],['created_at','bigint'],['last_seen_at','bigint']],
            'auth_passkey_handles'=>[['user_id','integer'],['handle','string',['length'=>64]]],
            'auth_passkeys'=>[['credential_hash','string',['length'=>64]],['user_id','integer'],['name','string',['length'=>80]],['record_json','text'],['version','integer',['default'=>1]],['created_at','bigint'],['last_used_at','bigint',['notnull'=>false]]],
        ];
        foreach($definitions as $name=>$columns) {
            if($schema->tablesExist([$name])) continue;$table=new Table($name);foreach($columns as $column) $table->addColumn($column[0],$column[1],$column[2]??[]);$table->setPrimaryKey([$columns[0][0]]);$table->addForeignKeyConstraint('auth_users',['user_id'],['id'],['onDelete'=>'CASCADE']);if($columns[0][0]!=='user_id') $table->addIndex(['user_id']);$schema->createTable($table);
        }
    }
    public static function down(Connection $db):void { $schema=$db->createSchemaManager();foreach(['auth_passkeys','auth_passkey_handles','auth_devices','auth_recovery_codes','auth_mfa'] as $name) if($schema->tablesExist([$name])) $schema->dropTable($name); }
}
