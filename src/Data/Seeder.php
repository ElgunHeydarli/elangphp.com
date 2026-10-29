<?php
declare(strict_types=1);
namespace Elang\Data;
use Elang\Database\Database;
interface Seeder { public function run(Database $database, Models $models): void; }
