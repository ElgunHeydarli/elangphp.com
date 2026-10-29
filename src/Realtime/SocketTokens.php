<?php
declare(strict_types=1);
namespace Elang\Realtime;
use Elang\{Auth\User,Auth\Users,Security\Encryption,Support\Config};
final class SocketTokens
{
    public function __construct(private Encryption $encryption,private Users $users,private Config $config) {}
    public function issue(User $user,string $channel,int $ttl=300,array $commands=[]):string
    {
        if(!$user->verified||$user->mustChangePassword||$ttl<10||$ttl>600) throw new \InvalidArgumentException('Use a verified account and a lifetime of 10–600 seconds.');
        if(!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9:_.-]{0,159}$/D',$channel)) throw new \InvalidArgumentException('Invalid channel.');
        foreach($commands as $command) if(!is_string($command)||!preg_match('/^[a-z][a-z0-9_.-]{1,63}$/D',$command)) throw new \InvalidArgumentException('Invalid socket command permission.');
        $payload=['commands'=>array_values(array_unique($commands)),'aud'=>rtrim($this->config->get('url'),'/'),'user'=>$user->id,'version'=>$user->authVersion,'channel'=>$channel,'exp'=>time()+$ttl];
        return rtrim(strtr(base64_encode($this->encryption->encrypt(json_encode($payload,JSON_THROW_ON_ERROR))),'+/','-_'),'=');
    }
    public function verify(#[\SensitiveParameter] string $token):?array
    {
        if(strlen($token)>4096||!preg_match('/^[A-Za-z0-9_-]+$/D',$token)) return null;
        try { $raw=base64_decode(strtr($token,'-_','+/'),true);if($raw===false) return null;$data=json_decode($this->encryption->decrypt($raw),true,32,JSON_THROW_ON_ERROR); }
        catch(\Throwable) { return null; }
        if(!is_array($data)||!isset($data['aud'],$data['user'],$data['version'],$data['channel'],$data['exp'],$data['commands'])||$data['aud']!==rtrim($this->config->get('url'),'/')||!is_int($data['exp'])||$data['exp']<=time()||!is_int($data['user'])||!is_string($data['channel'])||!is_array($data['commands'])) return null;
        $user=$this->users->find($data['user']);if(!$user||!$user->verified||$user->mustChangePassword||$user->authVersion!==$data['version']) return null;return $data;
    }
}
