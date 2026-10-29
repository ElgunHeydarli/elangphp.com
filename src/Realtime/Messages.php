<?php
declare(strict_types=1);
namespace Elang\Realtime;
use Elang\{Auth\User,Container\Container};
final class Messages
{
    private array $handlers=[];
    public function __construct(private Container $container) {}
    public function register(string $name,callable|array|string $handler):void
    {
        if(!preg_match('/^[a-z][a-z0-9_.-]{1,63}$/D',$name)||isset($this->handlers[$name])) throw new \InvalidArgumentException('Use a unique socket command name.');$this->handlers[$name]=$handler;
    }
    public function handle(string $name,array $payload,User $user,string $channel,array $commands):array
    {
        if(!isset($this->handlers[$name])||!in_array($name,$commands,true)) throw new \Elang\Http\HttpException(403,'This socket command is not permitted.');
        $result=$this->container->invoke($this->handlers[$name],compact('payload','user','channel'));if(!is_array($result)) throw new \LogicException('Socket command handlers must return an array.');return $result;
    }
}
