<?php
declare(strict_types=1);
namespace Elang\Orm;
use Doctrine\ORM\{EntityManager,ORMSetup};
use Doctrine\Common\EventManager;
use Elang\{Database\Database,Support\Config};
use Psr\Clock\ClockInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
final class Orm
{
    private ?EntityManager $manager=null;
    public function __construct(private Database $database,private Config $config,private ClockInterface $clock) {}
    public function proxyDirectory():string { return $this->config->get('paths.storage').'/cache/orm/proxies'; }
    private function cache():FilesystemAdapter { return new FilesystemAdapter('elang-orm',0,$this->config->get('paths.storage').'/cache/orm/metadata'); }
    public function clearMetadataCache():void
    {
        if($this->manager!==null){
            if($this->manager->getConnection()->isTransactionActive())throw new \LogicException('Finish the current transaction before clearing ORM metadata.');
            $this->manager->close();$this->manager=null;
        }
        if(!$this->cache()->clear())throw new \RuntimeException('Cannot clear the private ORM metadata cache.');
    }
    public function manager():EntityManager
    {
        if($this->manager!==null)return $this->manager;
        if(!class_exists(ORMSetup::class))throw new \LogicException('Install doctrine/orm:^3.7 to use the optional ORM integration.');
        $paths=$this->config->get('orm.paths',[$this->config->get('paths.base').'/app/Entities']);
        if(!is_array($paths)||!$paths)throw new \InvalidArgumentException('orm.paths must contain entity directories.');
        foreach($paths as $path)if(!is_string($path)||!is_dir($path))throw new \InvalidArgumentException('Each orm.paths entry must be an existing entity directory.');
        $directory=$this->proxyDirectory();if(!is_dir($directory)&&!mkdir($directory,0770,true)&&!is_dir($directory))throw new \RuntimeException('Cannot create the private ORM cache directory.');
        $development=in_array($this->config->get('environment'),['local','testing'],true);
        $cache=$this->cache();
        $settings=PHP_VERSION_ID>=80400
            ?ORMSetup::createAttributeMetadataConfig($paths,$development,null,$cache)
            :ORMSetup::createAttributeMetadataConfiguration($paths,$development,$directory,$cache);
        if(PHP_VERSION_ID>=80400)$settings->enableNativeLazyObjects(true);
        $settings->addFilter('elang_soft_delete',SoftDeleteFilter::class);
        $events=new EventManager();$events->addEventListener(['prePersist','preUpdate'],new TimestampListener($this->clock));
        $manager=new EntityManager($this->database->connection(),$settings,$events);
        $manager->getFilters()->enable('elang_soft_delete');
        return $this->manager=$manager;
    }
    public function transaction(callable $callback):mixed { return $this->manager()->wrapInTransaction($callback); }
    public function reset():void
    {
        if($this->database->connection()->isTransactionActive())throw new \LogicException('Finish the current transaction before resetting the ORM manager.');
        $this->manager?->close();$this->manager=null;
    }
    public function softDelete(SoftDeletable $entity):void
    {
        if(!$this->manager()->contains($entity))throw new \InvalidArgumentException('Soft deletion requires a managed entity.');
        $entity->markDeleted($this->clock->now()->setTimezone(new \DateTimeZone('UTC')));
    }
    public function restore(SoftDeletable $entity):void
    {
        if(!$this->manager()->contains($entity))throw new \InvalidArgumentException('Restoring requires a managed entity.');$entity->markDeleted(null);
    }
    public function withDeleted(callable $callback):mixed
    {
        $manager=$this->manager();$filters=$manager->getFilters();$enabled=$filters->isEnabled('elang_soft_delete');if($enabled)$filters->disable('elang_soft_delete');
        try{return $callback($manager);}finally{if($enabled)$filters->enable('elang_soft_delete');}
    }
    public function schemaSql():array { $manager=$this->manager();return (new \Doctrine\ORM\Tools\SchemaTool($manager))->getCreateSchemaSql($manager->getMetadataFactory()->getAllMetadata()); }
}
