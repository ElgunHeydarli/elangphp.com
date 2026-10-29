<?php
declare(strict_types=1);
use Doctrine\DBAL\{Connection,Schema\Table};
return new class implements Elang\Database\Migration {
    public function up(Connection $database): void {
        $table=new Table('posts');$table->addColumn('id','integer',['autoincrement'=>true]);$table->addColumn('title','string',['length'=>160]);$table->addColumn('body','text');$table->addColumn('author_id','integer');$table->addColumn('published','boolean',['default'=>false]);$table->addColumn('created_at','bigint');$table->setPrimaryKey(['id']);$table->addIndex(['published','id']);$table->addForeignKeyConstraint('auth_users',['author_id'],['id'],['onDelete'=>'CASCADE']);$database->createSchemaManager()->createTable($table);
    }
    public function down(Connection $database): void { $database->createSchemaManager()->dropTable('posts'); }
};
