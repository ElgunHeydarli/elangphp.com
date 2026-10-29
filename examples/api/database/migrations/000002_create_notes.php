<?php
declare(strict_types=1);
use Doctrine\DBAL\{Connection,Schema\Table};
return new class implements Elang\Database\Migration {
    public function up(Connection $database):void {
        $table=new Table('notes');$table->addColumn('id','integer',['autoincrement'=>true]);$table->addColumn('title','string',['length'=>160]);$table->addColumn('body','text');$table->addColumn('owner_id','integer');$table->addColumn('completed','boolean',['default'=>false]);$table->addColumn('created_at','bigint');$table->setPrimaryKey(['id']);$table->addIndex(['owner_id','id']);$table->addForeignKeyConstraint('auth_users',['owner_id'],['id'],['onDelete'=>'CASCADE']);$database->createSchemaManager()->createTable($table);
    }
    public function down(Connection $database):void { $database->createSchemaManager()->dropTable('notes'); }
};
