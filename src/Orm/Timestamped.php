<?php
declare(strict_types=1);
namespace Elang\Orm;
interface Timestamped { public function initializeTimestamps(\DateTimeImmutable $at):void;public function touch(\DateTimeImmutable $at):void; }
