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
    public function __construct(private Users $users,private Password $passwords,private Throttle $throttle,private Database $database) {}
    public function user(Request $request): ?User
    {
        if(!$request->hasSession()) return null;
        if($request->attributes->get('_elang_new_session')===true&&!$request->getSession()->isStarted()) return null;
        $identity=$request->getSession()->get('_auth');
        if(!is_array($identity)||!isset($identity['id'],$identity['version'])) return null;
        $user=$this->users->find((int)$identity['id']);
        if(!$user||$user->authVersion!==(int)$identity['version']) { $request->getSession()->remove('_auth');return null; }
        return $user;
    }
    public function attempt(Request $request,string $email,#[\SensitiveParameter] string $password): bool
    {
        $this->throttle->consume('login',$request->getClientIp()??'unknown',$email);
        $row=$this->users->rowByEmail($email);
        $valid=$this->passwords->verify($password,$row?$row['password']:self::DUMMY_HASH);
        if(!$row||!$valid) return false;
        if($this->passwords->needsRehash($row['password'])) $this->database->connection()->update('auth_users',['password'=>$this->passwords->hash($password)],['id'=>$row['id']]);
        $this->login($request,User::fromRow($row));return true;
    }
    public function login(Request $request,User $user): void
    {
        $session=$request->getSession();$session->migrate(true);$session->remove('_csrf');
        $session->set('_auth',['id'=>$user->id,'version'=>$user->authVersion]);
    }
    public function logout(Request $request): void { if($request->hasSession()) $request->getSession()->invalidate(); }
}
