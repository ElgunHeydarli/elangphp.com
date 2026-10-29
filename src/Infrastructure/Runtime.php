<?php
declare(strict_types=1);
namespace Elang\Infrastructure;
use Elang\Support\Config;
use Predis\Client;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\{FilesystemAdapter,RedisAdapter};
use Symfony\Component\Lock\{LockFactory,Store\FlockStore,Store\RedisStore};
final class Runtime
{
    private ?Client $client=null;
    private array $pools=[];
    private ?LockFactory $locks=null;
    public function __construct(private Config $config) {}
    public function distributed():bool { return $this->config->get('redis.url','')!==''; }
    public function prefix():string
    {
        $prefix=$this->config->get('redis.prefix','elang:'.substr(hash('sha256',$this->config->get('url','localhost')),0,16));
        if(!is_string($prefix)||!preg_match('/^[a-zA-Z0-9:_-]{1,80}$/D',$prefix)) throw new \InvalidArgumentException('Use a Redis prefix of 1–80 letters, numbers, colons, underscores, or hyphens.');return $prefix;
    }
    public function redis():Client
    {
        if(!$this->distributed()) throw new \LogicException('Configure REDIS_URL before using distributed services.');
        return $this->client??=new Client($this->config->get('redis.url'),['parameters'=>['timeout'=>2,'read_write_timeout'=>3]]);
    }
    public function command(array $arguments):mixed
    {
        $result=$this->redis()->executeRaw($arguments,$error);if($error) throw new \RuntimeException('A Redis operation failed. Check availability and ACL configuration.');return $result;
    }
    public function pool(string $name='app'):CacheItemPoolInterface
    {
        if(!preg_match('/^[a-z-]+$/D',$name)) throw new \InvalidArgumentException('Invalid cache namespace.');
        return $this->pools[$name]??=($this->distributed()?new RedisAdapter($this->redis(),str_replace(':','_',$this->prefix()).'_'.$name):new FilesystemAdapter('elang-'.$name,0,$this->config->get('paths.storage').'/cache'));
    }
    public function limiterStorage(string $name='limits'):\Symfony\Component\RateLimiter\Storage\StorageInterface { return $this->distributed()?new RedisLimiterStorage($this):new \Symfony\Component\RateLimiter\Storage\CacheStorage($this->pool($name)); }
    public function locks():LockFactory
    {
        return $this->locks??=new LockFactory($this->distributed()?new RedisStore($this->redis()):new FlockStore($this->config->get('paths.storage').'/locks'));
    }
    public function lock(string $resource,float $ttl=30):\Symfony\Component\Lock\LockInterface { return $this->locks()->createLock($this->prefix().':locks:'.$resource,$ttl); }
    public function topic():string { return $this->prefix().':events:notify'; }
}
