<?php
declare(strict_types=1);
use Doctrine\DBAL\Connection;
use Elang\Database\Migration;
return new class implements Migration {
    public function up(Connection $database):void { \Elang\Queue\Schema::up($database);\Elang\Realtime\Schema::up($database);\Elang\Http\Idempotency\Schema::up($database); }
    public function down(Connection $database):void { foreach(['elang_events','elang_schedule_runs','elang_jobs','elang_idempotency'] as $table) $database->createSchemaManager()->dropTable($table); }
};
