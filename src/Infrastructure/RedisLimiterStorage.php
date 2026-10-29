<?php
declare(strict_types=1);
namespace Elang\Infrastructure;
use Symfony\Component\RateLimiter\{LimiterStateInterface,Storage\StorageInterface,Policy\Window,Policy\SlidingWindow,Policy\TokenBucket,Policy\Rate};
/** Unlike a best-effort application cache, limiter persistence must report Redis errors. */
final class RedisLimiterStorage implements StorageInterface
{
    public function __construct(private Runtime $runtime) {}
    private function key(string $id):string { return $this->runtime->prefix().':limiter:'.hash('sha256',$id); }
    public function save(LimiterStateInterface $limiterState):void
    {
        $ttl=max(1,$limiterState->getExpirationTime()??86400);$this->runtime->command(['SET',$this->key($limiterState->getId()),serialize($limiterState),'EX',(string)$ttl]);
    }
    public function fetch(string $limiterStateId):?LimiterStateInterface
    {
        $raw=$this->runtime->command(['GET',$this->key($limiterStateId)]);if($raw===null) return null;if(!is_string($raw)) throw new \RuntimeException('Invalid limiter storage state.');
        $state=unserialize($raw,['allowed_classes'=>[Window::class,SlidingWindow::class,TokenBucket::class,Rate::class],'max_depth'=>16]);if(!$state instanceof LimiterStateInterface||$state->getId()!==$limiterStateId) throw new \RuntimeException('Invalid limiter storage state.');return $state;
    }
    public function delete(string $limiterStateId):void { $this->runtime->command(['DEL',$this->key($limiterStateId)]); }
}
