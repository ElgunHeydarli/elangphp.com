<?php
declare(strict_types=1);
use Doctrine\DBAL\Connection;
use Elang\Database\Migration;
return new class implements Migration {
    public function up(Connection $database): void { \Elang\Auth\Schema::up($database);\App\Cms\Schema::up($database); }
    public function down(Connection $database): void { foreach(['cms_revisions','cms_pages','auth_api_tokens','auth_tokens','auth_users'] as $table) $database->createSchemaManager()->dropTable($table); }
};
