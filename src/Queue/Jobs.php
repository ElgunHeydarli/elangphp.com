<?php
declare(strict_types=1);
namespace Elang\Queue;
use Elang\Container\Container;
final class Jobs
{
    private array $handlers=[];
    public function __construct(private Container $container) {}
    public function register(string $name,callable|array|string $handler):void
    {
        if(!preg_match('/^[a-z][a-z0-9_.-]{1,119}$/D',$name)||isset($this->handlers[$name])) throw new \InvalidArgumentException('Use a unique application job name.');$this->handlers[$name]=$handler;
    }
    public function has(string $name):bool { return isset($this->handlers[$name]); }
    public function run(string $name,array $payload):void
    {
        if(!$this->has($name)) throw new \RuntimeException('The job handler is not registered in this application.');$this->container->invoke($this->handlers[$name],['payload'=>$payload]);
    }
}
