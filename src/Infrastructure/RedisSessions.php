<?php
declare(strict_types=1);
namespace Elang\Infrastructure;
use Symfony\Component\Lock\LockInterface;
final class RedisSessions implements \SessionHandlerInterface,\SessionUpdateTimestampHandlerInterface
{
    private array $locks=[];
    public function __construct(private Runtime $runtime,private int $ttl=7200) {}
    private function key(string $id):string
    {
        if(!preg_match('/^[A-Za-z0-9,-]{16,128}$/D',$id)) throw new \InvalidArgumentException('Invalid session identifier.');return $this->runtime->prefix().':session:'.$id;
    }
    private function acquire(string $id):LockInterface
    {
        if(isset($this->locks[$id])) return $this->locks[$id];$lock=$this->runtime->lock('session:'.$id,30);$deadline=microtime(true)+5;
        do { if($lock->acquire()) return $this->locks[$id]=$lock;usleep(25000); }while(microtime(true)<$deadline);
        throw new \Elang\Http\HttpException(503,'Your session is busy. Retry shortly.',['Retry-After'=>'1']);
    }
    public function open(string $path,string $name):bool { return true; }
    public function close():bool { foreach($this->locks as $lock) $lock->release();$this->locks=[];return true; }
    public function read(string $id):string|false { $this->acquire($id);return $this->runtime->command(['GET',$this->key($id)])??''; }
    public function write(string $id,string $data):bool
    {
        $lock=$this->acquire($id);if(!$lock->isAcquired()) throw new \RuntimeException('The session reservation expired.');$lock->refresh(30);$this->runtime->command(['SET',$this->key($id),$data,'EX',(string)$this->ttl]);return true;
    }
    public function destroy(string $id):bool { $lock=$this->acquire($id);if(!$lock->isAcquired()) throw new \RuntimeException('The session reservation expired.');$lock->refresh(30);$this->runtime->command(['DEL',$this->key($id)]);return true; }
    public function gc(int $max_lifetime):int { return 0; }
    public function validateId(string $id):bool { if(!preg_match('/^[A-Za-z0-9,-]{16,128}$/D',$id)) return false;return (bool)$this->runtime->command(['EXISTS',$this->key($id)]); }
    public function updateTimestamp(string $id,string $data):bool { return $this->write($id,$data); }
}
