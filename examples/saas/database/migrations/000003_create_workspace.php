<?php
declare(strict_types=1);
use Doctrine\DBAL\{Connection,Schema\Table};
return new class implements Elang\Database\Migration {
    public function up(Connection $db):void
    {
        foreach([Elang\Tenancy\Schema::class,Elang\Storage\Schema::class,Elang\Notification\Schema::class,Elang\Observability\Schema::class] as $schema) $schema::up($db);if(!$db->createSchemaManager()->tablesExist(['saas_projects'])) { $t=new Table('saas_projects');$t->addColumn('id','string',['length'=>32]);$t->addColumn('team_id','string',['length'=>32]);$t->addColumn('title','string',['length'=>160]);$t->addColumn('status','string',['length'=>10]);$t->addColumn('version','integer');$t->addColumn('created_at','bigint');$t->setPrimaryKey(['id']);$t->addIndex(['team_id','created_at']);$t->addForeignKeyConstraint('elang_teams',['team_id'],['id'],['onDelete'=>'CASCADE']);$db->createSchemaManager()->createTable($t); }
    }
    public function down(Connection $db):void { $db->createSchemaManager()->dropTable('saas_projects'); }
};
