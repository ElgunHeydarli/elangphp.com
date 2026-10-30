<?php
declare(strict_types=1);
namespace Elang\Orm;
interface SoftDeletable { public function deletedAt():?\DateTimeImmutable;public function markDeleted(?\DateTimeImmutable $at):void; }
