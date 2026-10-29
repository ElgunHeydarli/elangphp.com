<?php
declare(strict_types=1);
namespace App;
use Elang\{Auth\User,Tenancy\Teams,Realtime\Events,Audit\Audit,Http\HttpException};
final class Projects
{
    public function __construct(private Teams $teams,private Events $events,private Audit $audit) {}
    private function title(string $title):string { $title=trim($title);if($title===''||mb_strlen($title)>160) throw new HttpException(422,'Project titles must contain 1–160 characters.');return $title; }
    public function list(User $user,string $team):array { return $this->teams->within($user,$team,'read',fn($db)=>$db->createQueryBuilder()->select('id','title','status','version','created_at')->from('saas_projects')->where('team_id = ?')->setParameter(0,$team)->orderBy('created_at','DESC')->addOrderBy('id')->setMaxResults(100)->executeQuery()->fetchAllAssociative()); }
    public function create(User $user,string $team,string $title):array
    {
        $title=$this->title($title);return $this->teams->within($user,$team,'write',function($db)use($user,$team,$title):array { $row=['id'=>bin2hex(random_bytes(16)),'team_id'=>$team,'title'=>$title,'status'=>'planned','version'=>1,'created_at'=>time()];$db->insert('saas_projects',$row);$this->events->publish('team:'.$team.':projects','project.created',['id'=>$row['id']]);$this->audit->append('team:'.$team,'project.created',$user->id,['id'=>$row['id']]);return $row; });
    }
    public function update(User $user,string $team,string $id,string $title,string $status,int $version):void
    {
        $title=$this->title($title);if(!in_array($status,['planned','active','done'],true)||$version<1) throw new HttpException(422,'Choose a project status and its current version.');$this->teams->within($user,$team,'write',function($db)use($user,$team,$id,$title,$status,$version):void { if(!$db->fetchOne('SELECT id FROM saas_projects WHERE id = ? AND team_id = ?',[$id,$team])) throw new HttpException(404,'Project not found.');if($db->executeStatement('UPDATE saas_projects SET title = ?, status = ?, version = version + 1 WHERE id = ? AND team_id = ? AND version = ?',[$title,$status,$id,$team,$version])!==1) throw new HttpException(409,'This project changed. Reload the workspace before saving again.');$this->events->publish('team:'.$team.':projects','project.updated',['id'=>$id]);$this->audit->append('team:'.$team,'project.updated',$user->id,['id'=>$id]); });
    }
    public function delete(User $user,string $team,string $id,int $version):void
    {
        $this->teams->within($user,$team,'write',function($db)use($user,$team,$id,$version):void { if(!$db->fetchOne('SELECT id FROM saas_projects WHERE id = ? AND team_id = ?',[$id,$team])) throw new HttpException(404,'Project not found.');if($db->delete('saas_projects',['id'=>$id,'team_id'=>$team,'version'=>$version])!==1) throw new HttpException(409,'This project changed. Reload before deleting.');$this->events->publish('team:'.$team.':projects','project.deleted',['id'=>$id]);$this->audit->append('team:'.$team,'project.deleted',$user->id,['id'=>$id]); });
    }
}
