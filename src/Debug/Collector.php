<?php
declare(strict_types=1);
namespace Elang\Debug;
use Elang\Support\Config;
use Symfony\Component\HttpFoundation\{Request,Response};
final class Collector
{
    private float $started = 0;
    private array $queries = [];
    private ?array $error = null;
    public function __construct(private Config $config) {}
    public function enabled(): bool { return $this->config->get('environment')==='local'&&$this->config->get('studio.enabled',true); }
    public function start(): void { $this->started=microtime(true);$this->queries=[];$this->error=null; }
    public function query(string $sql,float $milliseconds): void
    {
        if(!$this->enabled()||count($this->queries)>=200) return;
        $sql=preg_replace("/'(?:''|[^'])*'/s","'?'",$sql);
        $this->queries[]=['sql'=>mb_substr($sql,0,2000),'milliseconds'=>round($milliseconds,3)];
    }
    public function error(\Throwable $error): void
    {
        if(!$this->enabled()) return;
        $frames=[];
        foreach(array_slice($error->getTrace(),0,12) as $frame) $frames[]=['file'=>$this->relative($frame['file']??''),'line'=>$frame['line']??0,'call'=>($frame['class']??'').($frame['type']??'').$frame['function']];
        $this->error=['type'=>$error::class,'file'=>$this->relative($error->getFile()),'line'=>$error->getLine(),'frames'=>$frames];
    }
    private function relative(string $file): string
    {
        $base=$this->config->get('paths.base','');return $base&&str_starts_with($file,$base.'/')?substr($file,strlen($base)+1):basename($file);
    }
    public function finish(Request $request,Response $response,string $id): void
    {
        if(!$this->enabled()||str_starts_with($request->getPathInfo(),'/_elang/')) return;
        $directory=$this->config->get('paths.storage').'/studio';if(!is_dir($directory)) mkdir($directory,0770,true);
        $trace=['id'=>$id,'created_at'=>time(),'method'=>$request->getMethod(),'path'=>preg_replace('/[a-f0-9]{32,}/i','[redacted]',$request->getPathInfo()),'status'=>$response->getStatusCode(),'milliseconds'=>round((microtime(true)-$this->started)*1000,3),'php_memory_bytes'=>memory_get_peak_usage(true),'route'=>$request->attributes->get('_route'),'queries'=>$this->queries,'error'=>$this->error];
        file_put_contents($directory.'/'.$id.'.json',json_encode($trace,JSON_THROW_ON_ERROR),LOCK_EX);
        $files=glob($directory.'/*.json')?:[];usort($files,fn($a,$b)=>filemtime($b)<=>filemtime($a));foreach(array_slice($files,100) as $old) unlink($old);
        $response->headers->set('X-Elang-Trace',$id);
    }
}
