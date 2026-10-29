<?php
declare(strict_types=1);
namespace Elang\Auth;
use Elang\Support\Config;
use Elang\Http\HttpException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\RateLimiter\{RateLimiterFactory, Storage\CacheStorage};
use Symfony\Component\Lock\{LockFactory, Store\FlockStore};
final class Throttle
{
    public function __construct(private Config $config,private \Elang\Infrastructure\Runtime $runtime) {}
    public function consume(string $action, string $ip, string $identity = ''): void
    {
        $storage=$this->config->get('paths.storage');
        $factory=new RateLimiterFactory(['id'=>$this->runtime->prefix().':auth-'.$action,'policy'=>'fixed_window','limit'=>match($action) { 'login'=>5,'package-publish'=>30,'security-password'=>10,'mfa-confirm'=>6,default=>3 },'interval'=>'15 minutes'],$this->runtime->limiterStorage('auth-limits'),$this->runtime->locks());
        $limit=$factory->create(hash('sha256',$ip.'|'.Users::email($identity)))->consume();
        if(!$limit->isAccepted()) throw new HttpException(429,'Too many attempts. Please try again later.',['Retry-After'=>(string)max(1,$limit->getRetryAfter()->getTimestamp()-time())]);
        $global=new RateLimiterFactory(['id'=>$this->runtime->prefix().':auth-ip','policy'=>'fixed_window','limit'=>40,'interval'=>'15 minutes'],$this->runtime->limiterStorage('auth-limits'),$this->runtime->locks());
        if(!$global->create(hash('sha256',$ip))->consume()->isAccepted()) throw new HttpException(429,'Too many attempts. Please try again later.',['Retry-After'=>'900']);
    }
}
