<?php
declare(strict_types=1);
namespace Elang\Orm;
use Psr\Clock\ClockInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
final class TimestampListener
{
    public function __construct(private ClockInterface $clock) {}
    public function prePersist(LifecycleEventArgs $event):void { $entity=$event->getObject();if($entity instanceof Timestamped)$entity->initializeTimestamps($this->clock->now()->setTimezone(new \DateTimeZone('UTC'))); }
    public function preUpdate(\Doctrine\ORM\Event\PreUpdateEventArgs $event):void { $entity=$event->getObject();if($entity instanceof Timestamped)$entity->touch($this->clock->now()->setTimezone(new \DateTimeZone('UTC'))); }
}
