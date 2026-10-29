<?php
declare(strict_types=1);
namespace Elang\Outbox;
use Elang\{Database\Database,Infrastructure\Runtime};
final class Outbox
{
    private string $cursor='0-0';
    public function __construct(private Database $database,private Runtime $runtime) {}
    public function append(string $kind,array $payload):void
    {
        if(!$this->runtime->distributed()) return;if(!in_array($kind,['jobs','events'],true)) throw new \InvalidArgumentException('Unsupported outbox topic.');
        $this->database->connection()->insert('elang_outbox',['id'=>bin2hex(random_bytes(16)),'kind'=>$kind,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'created_at'=>time(),'published_at'=>null]);
    }
    public function dispatch(int $limit=100):int
    {
        if(!$this->runtime->distributed()) return 0;if($limit<1||$limit>1000) throw new \InvalidArgumentException('Use an outbox batch of 1–1000.');
        $connection=$this->database->connection();if($connection->isTransactionActive()) throw new \LogicException('Dispatch the outbox after the application transaction commits.');
        $rows=$connection->createQueryBuilder()->select('*')->from('elang_outbox')->where('published_at IS NULL')->orderBy('created_at')->addOrderBy('id')->setMaxResults($limit)->executeQuery()->fetchAllAssociative();$count=0;
        $lua="if redis.call('SET',KEYS[1],'1','NX','EX',604800) then local id=redis.call('XADD',KEYS[2],'MAXLEN','~',10000,'*','id',ARGV[1],'payload',ARGV[2]); if ARGV[3]~='' then redis.call('PUBLISH',ARGV[3],ARGV[2]); end; return id; end; return 'duplicate';";
        foreach($rows as $row) {
            $prefix=$this->runtime->prefix().':{outbox}';$topic=$row['kind']==='events'?$this->runtime->topic():'';
            $this->runtime->command(['EVAL',$lua,'2',$prefix.':seen:'.$row['id'],$prefix.':'.$row['kind'],$row['id'],$row['payload'],$topic]);
            $count+=$connection->executeStatement('UPDATE elang_outbox SET published_at = ? WHERE id = ? AND published_at IS NULL',[time(),$row['id']]);
        }
        return $count;
    }
    public function waitForJobs(int $milliseconds=1000):void
    {
        if(!$this->runtime->distributed()) { usleep($milliseconds*1000);return; }
        $this->dispatch();$stream=$this->runtime->prefix().':{outbox}:jobs';
        $result=$this->runtime->command(['XREAD','COUNT','100','BLOCK',(string)max(1,min(1000,$milliseconds)),'STREAMS',$stream,$this->cursor]);
        if(is_array($result)&&isset($result[0][1])&&is_array($result[0][1])) foreach($result[0][1] as $entry) if(is_array($entry)&&is_string($entry[0]??null)) $this->cursor=$entry[0];
    }
}
