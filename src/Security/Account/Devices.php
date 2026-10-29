<?php
declare(strict_types=1);
namespace Elang\Security\Account;
use Elang\{Auth\User,Database\Database,Database\Atomic};
use Symfony\Component\HttpFoundation\Request;
final class Devices
{
    public function __construct(private Database $database) {}
    private function id(Request $request):string { return hash('sha256',$request->getSession()->getId()); }
    public function register(Request $request,User $user):string
    {
        $id=$this->id($request);$agent=mb_substr(preg_replace('/[\x00-\x1f\x7f]/','',$request->headers->get('User-Agent','Unknown browser')),0,180);
        Atomic::insertIfMissing($this->database->connection(),'auth_devices',['id_hash'=>$id,'user_id'=>$user->id,'auth_version'=>$user->authVersion,'agent'=>$agent,'created_at'=>time(),'last_seen_at'=>time()]);return $id;
    }
    public function valid(string $id,User $user):bool
    {
        $connection=$this->database->connection();$row=$connection->fetchAssociative('SELECT last_seen_at FROM auth_devices WHERE id_hash = ? AND user_id = ? AND auth_version = ?',[$id,$user->id,$user->authVersion]);if(!$row) return false;
        if((int)$row['last_seen_at']<time()-60) $connection->update('auth_devices',['last_seen_at'=>time()],['id_hash'=>$id]);return true;
    }
    public function list(User $user,Request $request):array
    {
        $rows=$this->database->connection()->fetchAllAssociative('SELECT id_hash, agent, created_at, last_seen_at FROM auth_devices WHERE user_id = ? AND auth_version = ? ORDER BY last_seen_at DESC',[$user->id,$user->authVersion]);
        return array_map(fn($row)=>$row+['current'=>hash_equals($this->id($request),$row['id_hash'])],$rows);
    }
    public function revoke(User $user,string $id):bool { if(!preg_match('/^[a-f0-9]{64}$/D',$id)) return false;return $this->database->connection()->delete('auth_devices',['user_id'=>$user->id,'id_hash'=>$id])===1; }
    public function forget(Request $request):void { if($request->hasSession()&&$request->getSession()->isStarted()) $this->database->connection()->delete('auth_devices',['id_hash'=>$this->id($request)]); }
}
