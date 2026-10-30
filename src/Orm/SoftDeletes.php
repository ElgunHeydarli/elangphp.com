<?php
declare(strict_types=1);
namespace Elang\Orm;
trait SoftDeletes
{
    #[\Doctrine\ORM\Mapping\Column(name:'deleted_at',type:'datetime_immutable',nullable:true)]
    protected ?\DateTimeImmutable $deletedAt=null;
    public function deletedAt():?\DateTimeImmutable { return $this->deletedAt; }
    public function markDeleted(?\DateTimeImmutable $at):void { $this->deletedAt=$at; }
}
