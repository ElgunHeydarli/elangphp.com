<?php
declare(strict_types=1);
namespace Elang\Auth;
use Elang\Database\Database;
use Elang\Security\Password;
use Symfony\Component\HttpFoundation\Request;
final class Auth
{
    // Valid fixed Argon2id hash permits password verification work for unknown accounts.
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$ZHVtbXlzYWx0MTIzNDU2Nw$3F3UXiB8wNI4oN25DPyhsW6y4bY78m2EC2Dn4MIx2ZE';
    public function __construct(private Users $users,private Password $passwords,private Throttle $throttle,private Database $database,private \Elang\Security\Account\Devices $devices,private \Elang\Security\Account\Mfa $mfa,private \Elang\Audit\Audit $audit) {}
    public function user(Request $request): ?User
    {
        if(!$request->hasSession()) return null;
        if($request->attributes->get('_elang_new_session')===true&&!$request->getSession()->isStarted()) return null;
        $identity=$request->getSession()->get('_auth');
        if(!is_array($identity)||!isset($identity['id'],$identity['version'])) return null;
        $user=$this->users->find((int)$identity['id']);
        if(!$user||$user->authVersion!==(int)$identity['version']) { $request->getSession()->remove('_auth');return null; }
        $device=$identity['device']??null;if(!is_string($device)) { $device=$this->devices->register($request,$user);$request->getSession()->set('_auth',$identity+['device'=>$device]); }
        if(!$this->devices->valid($device,$user)) { $request->getSession()->remove('_auth');return null; }
        return $user;
    }
    public function attempt(Request $request,string $email,#[\SensitiveParameter] string $password): bool
    {
        $this->throttle->consume('login',$request->getClientIp()??'unknown',$email);
        if(mb_strlen($email)>190||strlen($password)>1024) { $this->passwords->verify('',self::DUMMY_HASH);return false; }
        $row=$this->users->rowByEmail($email);
        $valid=$this->passwords->verify($password,$row?$row['password']:self::DUMMY_HASH);
        if(!$row||!$valid) return false;
        if($this->passwords->needsRehash($row['password'])) $this->database->connection()->update('auth_users',['password'=>$this->passwords->hash($password)],['id'=>$row['id']]);
        $user=User::fromRow($row);
        if($this->mfa->enabled($user)) { $this->devices->forget($request);$session=$request->getSession();$session->migrate(true);$session->remove('_auth');$session->remove('_csrf');$session->set('_auth_pending',['id'=>$user->id,'version'=>$user->authVersion,'expires'=>time()+300]);return true; }
        $this->login($request,$user);return true;
    }
    public function pending(Request $request):?User
    {
        if(!$request->hasSession()) return null;$pending=$request->getSession()->get('_auth_pending');if(!is_array($pending)||($pending['expires']??0)<time()) { $request->getSession()->remove('_auth_pending');return null; }$user=$this->users->find((int)($pending['id']??0));
        return $user&&$user->authVersion===($pending['version']??null)&&$this->mfa->enabled($user)?$user:null;
    }
    public function secondFactor(Request $request,#[\SensitiveParameter] string $code):bool
    {
        $user=$this->pending($request);if(!$user) return false;$this->throttle->consume('mfa',$request->getClientIp()??'unknown',(string)$user->id);if(!$this->mfa->verify($user,$code)) return false;$this->login($request,$user);return true;
    }
    public function login(Request $request,User $user): void
    {
        $this->audit->append('user:'.$user->id,'auth.login',$user->id);$this->devices->forget($request);$session=$request->getSession();$session->migrate(true);$session->remove('_csrf');$session->remove('_auth_pending');
        $session->set('_auth',['id'=>$user->id,'version'=>$user->authVersion,'device'=>$this->devices->register($request,$user)]);
    }
    public function logout(Request $request): void { if($request->hasSession()) { $this->devices->forget($request);$request->getSession()->invalidate(); } }
}
