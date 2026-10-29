<?php
declare(strict_types=1);
namespace Elang\Observability;
use Elang\{Database\Database,Database\Atomic,Queue\Queue,Support\Config};
use Symfony\Component\HttpFoundation\{Request,Response};
final class Operations
{
    public function __construct(private Database $database,private Queue $queue,private Config $config) {}
    public function record(Request $request,Response $response,float $started):void
    {
        if(!$this->config->get('operations.enabled',false)) return;$route=$request->attributes->get('_route','unmatched');$label=$request->getMethod().' '.(is_string($route)?mb_substr($route,0,160):'unmatched');$bucket=intdiv(time(),60)*60;$key=hash('sha256',$label);$ms=(int)max(0,round((microtime(true)-$started)*1000));$db=$this->database->connection();
        try { $db->transactional(function()use($db,$bucket,$key,$label,$ms,$response):void { Atomic::insertIfMissing($db,'elang_http_metrics',['bucket'=>$bucket,'route_key'=>$key,'route_name'=>$label,'requests'=>0,'errors'=>0,'duration_ms'=>0,'max_ms'=>0]);$db->executeStatement('UPDATE elang_http_metrics SET requests = requests + 1, errors = errors + ?, duration_ms = duration_ms + ?, max_ms = CASE WHEN max_ms < ? THEN ? ELSE max_ms END WHERE bucket = ? AND route_key = ?',[$response->getStatusCode()>=500?1:0,$ms,$ms,$ms,$bucket,$key]); }); }catch(\Throwable) {}
    }
    public function heartbeat(string $id,string $kind,int $processed):void
    {
        if(!$this->config->get('operations.enabled',false)) return;try { $db=$this->database->connection();Atomic::insertIfMissing($db,'elang_workers',['id'=>$id,'kind'=>$kind,'last_seen'=>time(),'processed'=>$processed]);$db->update('elang_workers',['last_seen'=>time(),'processed'=>$processed],['id'=>$id]); }catch(\Throwable) {}
    }
    public function snapshot():array
    {
        $db=$this->database->connection();$queues=['queued'=>0,'reserved'=>0,'failed'=>0];foreach($db->fetchAllAssociative('SELECT state,COUNT(*) AS total FROM elang_jobs GROUP BY state') as $row) $queues[$row['state']]=(int)$row['total'];
        $workers=$db->fetchAllAssociative('SELECT id,kind,last_seen,processed FROM elang_workers WHERE last_seen > ? ORDER BY last_seen DESC',[time()-86400]);foreach($workers as &$worker) $worker['healthy']=(int)$worker['last_seen']>time()-120;unset($worker);
        return ['queues'=>$queues,'failed_jobs'=>$this->queue->failures(),'outbox_pending'=>(int)$db->fetchOne('SELECT COUNT(*) FROM elang_outbox WHERE published_at IS NULL'),'workers'=>$workers,'http'=>$db->fetchAllAssociative('SELECT route_name,SUM(requests) AS requests,SUM(errors) AS errors,SUM(duration_ms) AS duration_ms,MAX(max_ms) AS max_ms FROM elang_http_metrics WHERE bucket > ? GROUP BY route_name ORDER BY requests DESC',[time()-3600]),'window_seconds'=>3600,'generated_at'=>time()];
    }
    public function prune():void { $db=$this->database->connection();$db->executeStatement('DELETE FROM elang_http_metrics WHERE bucket < ?',[time()-86400]);$db->executeStatement('DELETE FROM elang_workers WHERE last_seen < ?',[time()-604800]);$db->executeStatement('DELETE FROM elang_outbox WHERE published_at < ?',[time()-604800]); }
}
