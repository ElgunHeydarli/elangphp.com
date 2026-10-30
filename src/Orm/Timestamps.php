<?php
declare(strict_types=1);
namespace Elang\Orm;
trait Timestamps
{
    #[\Doctrine\ORM\Mapping\Column(name:'created_at',type:'datetime_immutable')]
    protected ?\DateTimeImmutable $createdAt=null;
    #[\Doctrine\ORM\Mapping\Column(name:'updated_at',type:'datetime_immutable')]
    protected ?\DateTimeImmutable $updatedAt=null;
    public function createdAt():?\DateTimeImmutable { return $this->createdAt; }
    public function updatedAt():?\DateTimeImmutable { return $this->updatedAt; }
    public function initializeTimestamps(\DateTimeImmutable $at):void { $this->createdAt??=$at;$this->updatedAt??=$at; }
    public function touch(\DateTimeImmutable $at):void { $this->updatedAt=$at; }
}
