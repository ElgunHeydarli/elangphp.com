<?php
declare(strict_types=1);
namespace Elang\Database;
use Doctrine\DBAL\Connection;
interface Migration
{
    public function up(Connection $database): void;
    public function down(Connection $database): void;
}
