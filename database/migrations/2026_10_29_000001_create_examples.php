<?php
declare(strict_types=1);
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Elang\Database\Migration;
return new class implements Migration {
    public function up(Connection $database): void
    {
        $table = new Table('examples');
        $table->addColumn('id', 'integer');
        $table->addColumn('title', 'string', ['length' => 160]);
        $table->addColumn('category', 'string', ['length' => 40]);
        $table->setPrimaryKey(['id']);
        $database->createSchemaManager()->createTable($table);
        $database->insert('examples', ['id' => 1, 'title' => 'Your first Elang route', 'category' => 'Getting started']);
        $database->insert('examples', ['id' => 2, 'title' => 'A small JSON API', 'category' => 'HTTP']);
        $database->insert('examples', ['id' => 3, 'title' => 'Queries with bound parameters', 'category' => 'Database']);
    }
    public function down(Connection $database): void { $database->createSchemaManager()->dropTable('examples'); }
};
