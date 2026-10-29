<?php
declare(strict_types=1);
return new class implements Elang\Database\Migration {
    public function up(Doctrine\DBAL\Connection $db):void { foreach([Elang\Auth\Schema::class,Elang\Queue\Schema::class,Elang\Realtime\Schema::class,Elang\Tenancy\Schema::class,Elang\Storage\Schema::class,Elang\Notification\Schema::class,Elang\Observability\Schema::class] as $schema) $schema::up($db); }
    public function down(Doctrine\DBAL\Connection $db):void { throw new RuntimeException('Platform tables contain account security and private data. Review a forward repair or restore a verified backup; automatic destructive rollback is disabled.'); }
};
