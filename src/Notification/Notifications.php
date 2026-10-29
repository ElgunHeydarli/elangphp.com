<?php
declare(strict_types=1);
namespace Elang\Notification;
use Elang\{Auth\User,Database\Database,Queue\Queue,Security\Encryption};
final class Notifications
{
    public function __construct(private Database $database,private Queue $queue,private Encryption $encryption) {}
    public function send(User $user,string $subject,string $body,?string $url=null,bool $mail=false):string
    {
        if(trim($subject)===''||mb_strlen($subject)>160||strlen($body)>16000||$url!==null&&(!str_starts_with($url,'/')||str_starts_with($url,'//')||strlen($url)>500||preg_match('/[\x00-\x20\\\\]/',$url))) throw new \InvalidArgumentException('Supply a subject, bounded plain text, and an optional local URL.');
        return $this->database->transaction(function($db)use($user,$subject,$body,$url,$mail):string { $id=bin2hex(random_bytes(16));$db->insert('elang_notifications',['id'=>$id,'user_id'=>$user->id,'subject'=>$subject,'body'=>$body,'url'=>$url,'created_at'=>time(),'read_at'=>null]);if($mail) $this->queue->push('notification.mail',['sealed'=>$this->encryption->encrypt(json_encode(['to'=>$user->email,'subject'=>$subject,'body'=>$body],JSON_THROW_ON_ERROR))]);return $id; });
    }
    public function inbox(User $user):array { return $this->database->connection()->createQueryBuilder()->select('id','subject','body','url','created_at','read_at')->from('elang_notifications')->where('user_id = ?')->setParameter(0,$user->id)->orderBy('created_at','DESC')->addOrderBy('id')->setMaxResults(100)->executeQuery()->fetchAllAssociative(); }
    public function markRead(User $user,string $id):bool { return $this->database->connection()->executeStatement('UPDATE elang_notifications SET read_at = ? WHERE id = ? AND user_id = ? AND read_at IS NULL',[time(),$id,$user->id])===1; }
}
