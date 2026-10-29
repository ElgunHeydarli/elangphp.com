<?php
declare(strict_types=1);
namespace Elang\Queue;
use Elang\{Database\Database,Support\Config};
final class Queue
{
    public function __construct(private Database $database,private Jobs $jobs,private Config $config,private ?\Elang\Outbox\Outbox $outbox=null,private ?\Elang\Observability\Telemetry $telemetry=null) {}
    public function push(string $name,array $payload=[],int $delay=0):string
    {
        if(!$this->jobs->has($name)) throw new \InvalidArgumentException('Register the job handler before dispatching it.');
        if($delay<0||$delay>2592000) throw new \InvalidArgumentException('Delay must be between zero and 30 days.');
        $json=json_encode($payload,JSON_THROW_ON_ERROR);if(strlen($json)>65536) throw new \InvalidArgumentException('Job payloads must fit within 64 KB.');
        $id=bin2hex(random_bytes(16));$this->database->connection()->transactional(function($connection)use($id,$name,$json,$delay):void { $connection->insert('elang_jobs',['id'=>$id,'name'=>$name,'payload'=>$json,'trace_context'=>json_encode($this->telemetry?->carrier()??[],JSON_THROW_ON_ERROR),'state'=>'queued','attempts'=>0,'available_at'=>time()+$delay,'created_at'=>time(),'reserved_until'=>null,'reservation'=>null,'last_error'=>null]);$this->outbox?->append('jobs',['id'=>$id]); });return $id;
    }
    public function claim():?array
    {
        $connection=$this->database->connection();$now=time();$lease=$this->config->get('queue.lease_seconds',120);
        if(!is_int($lease)||$lease<10||$lease>86400) throw new \InvalidArgumentException('queue.lease_seconds must be 10–86400 seconds.');
        $query=$connection->createQueryBuilder()->select('id')->from('elang_jobs')->where('(state = ? AND available_at <= ?) OR (state = ? AND reserved_until <= ?)')->orderBy('available_at')->addOrderBy('id')->setMaxResults(20)->setParameters(['queued',$now,'reserved',$now]);
        foreach($query->executeQuery()->fetchFirstColumn() as $id) {
            $reservation=bin2hex(random_bytes(16));
            $changed=$connection->executeStatement('UPDATE elang_jobs SET state = ?, attempts = attempts + 1, reserved_until = ?, reservation = ? WHERE id = ? AND ((state = ? AND available_at <= ?) OR (state = ? AND reserved_until <= ?))',['reserved',$now+$lease,$reservation,$id,'queued',$now,'reserved',$now]);
            if($changed===1) return $connection->fetchAssociative('SELECT * FROM elang_jobs WHERE id = ? AND reservation = ?',[$id,$reservation])?:null;
        }return null;
    }
    public function complete(array $job):bool
    {
        return $this->database->connection()->executeStatement('DELETE FROM elang_jobs WHERE id = ? AND reservation = ? AND state = ? AND reserved_until > ?',[$job['id'],$job['reservation'],'reserved',time()])===1;
    }
    public function failed(array $job,\Throwable $error):void
    {
        $max=$this->config->get('queue.max_attempts',5);$final=(int)$job['attempts']>=$max;
        $this->database->connection()->executeStatement('UPDATE elang_jobs SET state = ?, available_at = ?, reserved_until = NULL, reservation = NULL, last_error = ? WHERE id = ? AND reservation = ? AND state = ? AND reserved_until > ?',[$final?'failed':'queued',time()+min(3600,2**min((int)$job['attempts'],11)),mb_substr($error::class,0,200),$job['id'],$job['reservation'],'reserved',time()]);
    }
    public function retry(string $id):bool { return $this->database->connection()->executeStatement('UPDATE elang_jobs SET state = ?, attempts = 0, available_at = ?, last_error = NULL WHERE id = ? AND state = ?',['queued',time(),$id,'failed'])===1; }
    public function failures():array { return $this->database->connection()->createQueryBuilder()->select('id','name','attempts','last_error','created_at')->from('elang_jobs')->where('state = ?')->setParameter(0,'failed')->orderBy('created_at','DESC')->setMaxResults(100)->executeQuery()->fetchAllAssociative(); }
    public function wait():void { if($this->outbox) $this->outbox->waitForJobs();else usleep(500000); }
    public function workOne():bool
    {
        $job=$this->claim();if(!$job) return false;
        try {
            if((int)$job['attempts']>$this->config->get('queue.max_attempts',5)) throw new \RuntimeException('Job retry limit exhausted.');
            $payload=json_decode($job['payload'],true,64,JSON_THROW_ON_ERROR);if(!is_array($payload)) throw new \UnexpectedValueException('Invalid job payload.');$run=fn()=>$this->jobs->run($job['name'],$payload);if($this->telemetry) $this->telemetry->job($job['name'],json_decode($job['trace_context']??'[]',true,8,JSON_THROW_ON_ERROR),$run);else $run();$this->complete($job);
        } catch(\Throwable $error) { $this->failed($job,$error); }
        return true;
    }
}
