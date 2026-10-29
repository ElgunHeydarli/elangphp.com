<?php
declare(strict_types=1);
return new class implements Elang\Database\Migration {
    public function up(Doctrine\DBAL\Connection $database):void { Elang\Auth\Schema::up($database); }
    public function down(Doctrine\DBAL\Connection $database):void { Elang\Auth\Schema::down($database); }
};
