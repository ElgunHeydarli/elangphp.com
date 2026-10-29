<?php
declare(strict_types=1);
namespace Elang\Storage;
use Elang\{Auth\User,Auth\Users,Database\Database,Tenancy\Teams,Support\Config,Http\HttpException};
final class Files
{
    public function __construct(private Database $database,private Disks $disks,private Images $images,private Teams $teams,private Users $users,private Config $config) {}
    private function authorize(User $user,?string $team,string $permission,callable $callback):mixed
    {
        if($team!==null) return $this->teams->within($user,$team,$permission,fn()=>$callback());$fresh=$this->users->find($user->id);if(!$fresh||$fresh->authVersion!==$user->authVersion||!$fresh->verified||$fresh->mustChangePassword) throw new HttpException(403,'A verified current account is required.');return $callback();
    }
    public function put(User $user,string $name,string $bytes,?string $team=null,bool $image=false):array
    {
        $name=trim(str_replace(['\\','/'], '-', $name));if($name===''||mb_strlen($name)>180||preg_match('/[\x00-\x1f\x7f]/',$name)) throw new HttpException(422,'Choose a safe filename of 1–180 characters.');$limit=$this->config->get('files.max_bytes',10485760);if(strlen($bytes)>$limit||$bytes==='') throw new HttpException(413,'The upload is empty or exceeds the configured file limit.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes)?:'application/octet-stream';if($image) { $safe=$this->images->sanitize($bytes);$bytes=$safe['bytes'];$mime=$safe['mime'];$name=pathinfo($name,PATHINFO_FILENAME).'.'.$safe['extension'];if(strlen($bytes)>$limit) throw new HttpException(413,'The encoded image exceeds the configured file limit.'); }
        return $this->authorize($user,$team,'write',function()use($user,$team,$name,$bytes,$mime):array {
            $id=bin2hex(random_bytes(16));$disk=$this->config->get('files.disk','local');$key=($team===null?'users/'.$user->id:'teams/'.$team).'/'.$id;$row=['id'=>$id,'user_id'=>$user->id,'team_id'=>$team,'disk'=>$disk,'object_key'=>$key,'name'=>$name,'mime'=>$mime,'bytes'=>strlen($bytes),'sha256'=>hash('sha256',$bytes),'created_at'=>time()];$fs=$this->disks->get($disk);$fs->write($key,$bytes,['visibility'=>'private']);
            try { $this->database->connection()->insert('elang_files',$row); }catch(\Throwable $error) { try { $fs->delete($key); }catch(\Throwable) {}throw $error; }return $this->public($row);
        });
    }
    private function public(array $row):array { unset($row['object_key'],$row['disk']);return $row; }
    public function list(User $user,?string $team=null):array
    {
        return $this->authorize($user,$team,'read',function()use($user,$team):array { $q=$this->database->connection()->createQueryBuilder()->select('*')->from('elang_files')->where($team===null?'user_id = ? AND team_id IS NULL':'team_id = ?')->setParameter(0,$team??$user->id)->orderBy('created_at','DESC')->addOrderBy('id')->setMaxResults(100);return array_map($this->public(...),$q->executeQuery()->fetchAllAssociative()); });
    }
    private function row(string $id):array { if(!preg_match('/^[a-f0-9]{32}$/D',$id)) throw new HttpException(404,'File not found.');return $this->database->connection()->fetchAssociative('SELECT * FROM elang_files WHERE id = ?',[$id])?:throw new HttpException(404,'File not found.'); }
    private function owned(User $user,array $row,string $permission,callable $callback):mixed
    {
        if($row['team_id']===null&&(int)$row['user_id']!==$user->id) throw new HttpException(404,'File not found.');return $this->authorize($user,$row['team_id'],$permission,$callback);
    }
    public function download(User $user,string $id):array { $row=$this->row($id);return $this->owned($user,$row,'read',fn()=>['metadata'=>$this->public($row),'stream'=>$this->disks->get($row['disk'])->readStream($row['object_key'])]); }
    public function delete(User $user,string $id):void
    {
        $row=$this->row($id);$this->owned($user,$row,'write',function()use($id,$row):void { $this->disks->get($row['disk'])->delete($row['object_key']);$this->database->connection()->delete('elang_files',['id'=>$id]); });
    }
    public function temporaryUrl(User $user,string $id,int $seconds=300):string
    {
        if($seconds<1||$seconds>3600) throw new \InvalidArgumentException('Temporary URLs last 1–3600 seconds.');$row=$this->row($id);return $this->owned($user,$row,'read',function()use($user,$id,$seconds):string { $expires=time()+$seconds;$signature=$this->signature($user->id,$id,$expires);return '/files/'.$id.'/download?expires='.$expires.'&signature='.$signature; });
    }
    private function signature(int $user,string $id,int $expires):string
    {
        $key=base64_decode($this->config->get('key',''),true);if(!is_string($key)||strlen($key)!==32) throw new \RuntimeException('Configure APP_KEY first.');return hash_hmac('sha256','elang.file.v1|'.$user.'|'.$id.'|'.$expires,$key);
    }
    public function verifyUrl(User $user,string $id,int $expires,string $signature):bool { return $expires>=time()&&$expires<=time()+3600&&hash_equals($this->signature($user->id,$id,$expires),$signature); }
}
