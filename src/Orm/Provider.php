<?php
declare(strict_types=1);
namespace Elang\Orm;
use Elang\{Application,Container\Container,Contracts\ServiceProvider};
use Doctrine\ORM\{EntityManager,EntityManagerInterface};
final class Provider implements ServiceProvider
{
    public function register(Container $container):void
    {
        if($container->has('elang.orm.registered'))return;
        if(!class_exists(EntityManager::class))throw new \LogicException('The optional ORM integration requires composer require doctrine/orm:^3.7.');
        $container->singleton(Orm::class,fn(Container $c)=>new Orm($c->get(\Elang\Database\Database::class),$c->get(\Elang\Support\Config::class),$c->get(\Psr\Clock\ClockInterface::class)));
        // Resolve the current manager after reset; do not cache a closed instance in DI.
        foreach([EntityManager::class,EntityManagerInterface::class] as $id)$container->bind($id,fn(Container $c)=>$c->get(Orm::class)->manager());
        $container->instance('elang.orm.registered',true);
    }
    public function boot(Application $app):void
    {
        if($app->container->has('elang.orm.commands'))return;
        foreach((new Commands($app))->all() as $command)$app->command($command);
        $app->container->instance('elang.orm.commands',true);
    }
}
