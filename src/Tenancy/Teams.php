<?php
declare(strict_types=1);
namespace Elang\Tenancy;
use Elang\{Auth\User,Auth\Users,Database\Database,Database\Atomic,Audit\Audit,Http\HttpException};
final class Teams
{
    private const PERMISSIONS=['owner'=>['read','write','members','admin'],'admin'=>['read','write','members'],'member'=>['read','write'],'viewer'=>['read']];
    public function __construct(private Database $database,private Users $users,private Audit $audit) {}
    public function create(User $actor,string $name):array
    {
        $this->identity($actor);$name=trim($name);if($name===''||mb_strlen($name)>80) throw new HttpException(422,'Team names must contain 1–80 characters.');
        return $this->database->transaction(function($db)use($actor,$name):array { $team=['id'=>bin2hex(random_bytes(16)),'name'=>$name,'created_at'=>time()];$db->insert('elang_teams',$team);$db->insert('elang_members',['team_id'=>$team['id'],'user_id'=>$actor->id,'role'=>'owner','created_at'=>time()]);$this->audit->append('team:'.$team['id'],'team.created',$actor->id);return $team+['role'=>'owner']; });
    }
    private function identity(User $actor):void
    {
        $fresh=$this->users->find($actor->id);if(!$fresh||$fresh->authVersion!==$actor->authVersion||!$fresh->verified||$fresh->mustChangePassword) throw new HttpException(403,'A verified current account is required.');
    }
    public function list(User $actor):array { $this->identity($actor);return $this->database->connection()->fetchAllAssociative('SELECT t.id,t.name,t.created_at,m.role FROM elang_teams t JOIN elang_members m ON m.team_id = t.id WHERE m.user_id = ? ORDER BY t.created_at,t.id',[$actor->id]); }
    /** The organization lock makes membership checks and business writes one transaction. Never retain this context between requests/jobs. */
    public function within(User $actor,string $team,string $permission,callable $callback):mixed
    {
        $this->identity($actor);if(!preg_match('/^[a-f0-9]{32}$/D',$team)) throw new HttpException(404,'Team not found.');
        return $this->database->transaction(function($db)use($actor,$team,$permission,$callback):mixed {
            Atomic::lockRow($db,'elang_teams','id',$team);$row=Atomic::currentRow($db,'SELECT t.id,t.name,t.created_at,m.role FROM elang_teams t JOIN elang_members m ON m.team_id = t.id WHERE t.id = ? AND m.user_id = ?',[$team,$actor->id]);
            if(!$row) throw new HttpException(404,'Team not found.');if(!in_array($permission,self::PERMISSIONS[$row['role']]??[],true)) throw new HttpException(403,'Your team role does not allow this action.');return $callback($db,$row);
        });
    }
    public function members(User $actor,string $team):array { return $this->within($actor,$team,'read',fn($db)=>$db->fetchAllAssociative('SELECT m.user_id,m.role,u.name FROM elang_members m JOIN auth_users u ON u.id = m.user_id WHERE m.team_id = ? ORDER BY m.created_at,m.user_id',[$team])); }
    public function invite(User $actor,string $team,string $email,string $role='member'):string
    {
        $email=Users::email($email);if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||!in_array($role,['admin','member','viewer'],true)) throw new HttpException(422,'Choose a valid email address and invitation role.');
        return $this->within($actor,$team,'members',function($db,$context)use($actor,$team,$email,$role):string {
            if($role==='admin'&&$context['role']!=='owner') throw new HttpException(403,'Only the owner can appoint administrators.');$raw=bin2hex(random_bytes(32));$db->delete('elang_invites',['team_id'=>$team,'email'=>$email]);$db->insert('elang_invites',['token_hash'=>hash('sha256',$raw),'team_id'=>$team,'email'=>$email,'role'=>$role,'expires_at'=>time()+604800]);$this->audit->append('team:'.$team,'team.invited',$actor->id,['role'=>$role]);return $raw;
        });
    }
    public function accept(User $actor,#[\SensitiveParameter] string $token):?array
    {
        $this->identity($actor);if(!preg_match('/^[a-f0-9]{64}$/D',$token)) return null;$db=$this->database->connection();$hash=hash('sha256',$token);$invite=$db->fetchAssociative('SELECT * FROM elang_invites WHERE token_hash = ?',[$hash]);if(!$invite) return null;
        return $db->transactional(function($db)use($actor,$hash,$invite):?array {
            Atomic::lockRow($db,'elang_teams','id',$invite['team_id']);$row=Atomic::currentRow($db,'SELECT * FROM elang_invites WHERE token_hash = ? AND email = ? AND expires_at > ?',[$hash,$actor->email,time()]);if(!$row) return null;
            if(Atomic::currentRow($db,'SELECT role FROM elang_members WHERE team_id = ? AND user_id = ?',[$row['team_id'],$actor->id])===false) $db->insert('elang_members',['team_id'=>$row['team_id'],'user_id'=>$actor->id,'role'=>$row['role'],'created_at'=>time()]);$db->delete('elang_invites',['token_hash'=>$hash]);$this->audit->append('team:'.$row['team_id'],'team.joined',$actor->id);return $db->fetchAssociative('SELECT id,name,created_at FROM elang_teams WHERE id = ?',[$row['team_id']])?:null;
        });
    }
    public function role(User $actor,string $team,int $member,string $role):void
    {
        if(!in_array($role,['admin','member','viewer'],true)) throw new HttpException(422,'Choose admin, member, or viewer. Use transfer() to change the owner.');
        $this->within($actor,$team,'admin',function($db)use($actor,$team,$member,$role):void { $target=Atomic::currentRow($db,'SELECT role FROM elang_members WHERE team_id = ? AND user_id = ?',[$team,$member]);if($target===false) throw new HttpException(404,'Member not found.');if($target['role']==='owner') throw new HttpException(409,'Transfer ownership before changing the owner.');$db->update('elang_members',['role'=>$role],['team_id'=>$team,'user_id'=>$member]);$this->audit->append('team:'.$team,'team.role_changed',$actor->id,['member'=>$member,'role'=>$role]); });
    }
    public function remove(User $actor,string $team,int $member):bool
    {
        return $this->within($actor,$team,'members',function($db,$context)use($actor,$team,$member):bool { $target=Atomic::currentRow($db,'SELECT role FROM elang_members WHERE team_id = ? AND user_id = ?',[$team,$member]);$role=$target?$target['role']:null;if($role==='owner'||$role==='admin'&&$context['role']!=='owner') throw new HttpException(403,'Only the owner can remove administrators; transfer ownership before leaving.');$done=$db->delete('elang_members',['team_id'=>$team,'user_id'=>$member])===1;if($done) $this->audit->append('team:'.$team,'team.member_removed',$actor->id,['member'=>$member]);return $done; });
    }
    public function transfer(User $actor,string $team,int $member):void
    {
        $this->within($actor,$team,'admin',function($db)use($actor,$team,$member):void { if($member===$actor->id) return;if(!Atomic::currentRow($db,'SELECT user_id FROM elang_members WHERE team_id = ? AND user_id = ?',[$team,$member])) throw new HttpException(404,'Member not found.');$db->update('elang_members',['role'=>'admin'],['team_id'=>$team,'user_id'=>$actor->id]);$db->update('elang_members',['role'=>'owner'],['team_id'=>$team,'user_id'=>$member]);$this->audit->append('team:'.$team,'team.owner_transferred',$actor->id,['member'=>$member]); });
    }
}
