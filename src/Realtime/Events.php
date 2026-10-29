<?php
declare(strict_types=1);
namespace Elang\Realtime;
use Elang\Database\Database;
final class Events
{
    public function __construct(private Database $database) {}
    private function channel(string $channel):void { if(!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9:_.-]{0,159}$/D',$channel)) throw new \InvalidArgumentException('Invalid realtime channel.'); }
    public function publish(string $channel,string $event,array $payload):int
    {
        $this->channel($channel);if(!preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]{0,63}$/D',$event)) throw new \InvalidArgumentException('Invalid event name.');
        $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);if(strlen($json)>16384) throw new \InvalidArgumentException('Realtime payloads must fit within 16 KB.');
        $connection=$this->database->connection();$connection->insert('elang_events',['channel'=>$channel,'event'=>$event,'payload'=>$json,'created_at'=>time()]);return (int)$connection->lastInsertId();
    }
    public function after(string $channel,int $id=0,int $limit=100):array
    {
        $this->channel($channel);if($id<0||$limit<1||$limit>100) throw new \InvalidArgumentException('Use a non-negative event ID and a limit of 1–100.');
        $rows=$this->database->connection()->createQueryBuilder()->select('id','event','payload','created_at')->from('elang_events')->where('channel = ? AND id > ?')->setParameters([$channel,$id])->orderBy('id')->setMaxResults($limit)->executeQuery()->fetchAllAssociative();
        return array_map(fn($row)=>['id'=>(int)$row['id'],'event'=>$row['event'],'data'=>json_decode($row['payload'],true,64,JSON_THROW_ON_ERROR),'created_at'=>(int)$row['created_at']],$rows);
    }
    public function latest(string $channel):int { $this->channel($channel);return (int)$this->database->connection()->fetchOne('SELECT MAX(id) FROM elang_events WHERE channel = ?',[$channel]); }
    public function prune(int $before):int { if($before>time()-3600) throw new \InvalidArgumentException('Keep at least one hour of replay history.');return $this->database->connection()->executeStatement('DELETE FROM elang_events WHERE created_at < ?',[$before]); }
}
