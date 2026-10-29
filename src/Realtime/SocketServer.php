<?php
declare(strict_types=1);
namespace Elang\Realtime;
use Elang\{Application,Auth\Users};
use Workerman\{Worker,Timer,Connection\TcpConnection,Protocols\Http\Request};
final class SocketServer
{
    public function __construct(private Application $app,private Events $events,private SocketTokens $tokens,private Messages $messages,private Users $users) {}
    public function run(string $host='127.0.0.1',int $port=8081,int $workers=1):void
    {
        if(!class_exists(Worker::class)) throw new \RuntimeException('Install elang/realtime to enable the WebSocket server.');
        if(!extension_loaded('pcntl')||!extension_loaded('posix')) throw new \RuntimeException('The WebSocket server requires Linux/macOS CLI PHP with pcntl and posix.');
        foreach(['pcntl_signal','pcntl_signal_dispatch','pcntl_fork','pcntl_wait','pcntl_alarm','posix_getpid'] as $function) if(!function_exists($function)) throw new \RuntimeException('WebSocket CLI requires enabled process-control functions. Use a dedicated CLI PHP configuration; do not change your web pool settings.');
        if(!filter_var($host,FILTER_VALIDATE_IP)||$port<1024||$port>65535||$workers<1||$workers>32) throw new \InvalidArgumentException('Choose an IP, a port from 1024–65535, and 1–32 workers.');
        $storage=$this->app->config->get('paths.storage');Worker::$pidFile=$storage.'/realtime.pid';Worker::$logFile=$storage.'/logs/realtime.log';
        $worker=new Worker('websocket://'.(str_contains($host,':')?'['.$host.']':$host).':'.$port);$worker->name='Elang Realtime';$worker->count=$workers;$connections=[];
        $worker->onConnect=function(TcpConnection $connection)use(&$connections):void {
            if(count($connections)>=1000) { $connection->close();return; }
            $connection->maxPackageSize=32768;$connection->maxSendBufferSize=262144;$connection->context->elangAccepted=false;$connection->context->elangDeadline=time()+10;
            $connections[$connection->id]=$connection;
        };
        $worker->onWebSocketConnect=function(TcpConnection $connection,Request $request):void {
            try {
                $origin=$request->header('origin');$allowed=array_merge([rtrim($this->app->config->get('url'),'/')],$this->app->config->get('realtime.origins',[]));$protocols=array_map('trim',explode(',',$request->header('sec-websocket-protocol','')));$token=null;
                foreach($protocols as $protocol) if(str_starts_with($protocol,'token.')) $token=substr($protocol,6);
                if($request->path()!=='/realtime/socket'||($origin!==null&&!in_array($origin,$allowed,true))||!in_array('elang',$protocols,true)||$token===null) throw new \RuntimeException('Invalid handshake.');
                $claims=$this->tokens->verify($token);if(!$claims) throw new \RuntimeException('Invalid token.');
                $after=$request->get('after');if($after!==null&&(!is_string($after)||!ctype_digit($after)||strlen($after)>15)) throw new \RuntimeException('Invalid replay cursor.');
                $connection->context->elangClaims=$claims;$connection->context->elangToken=$token;$connection->context->elangCursor=$after===null?$this->events->latest($claims['channel']):(int)$after;
                $connection->context->elangAccepted=true;$connection->context->elangChecked=time();$connection->context->elangWindow=time();$connection->context->elangMessages=0;$connection->headers=['Sec-WebSocket-Protocol: elang'];
            } catch(\Throwable) { $connection->close("HTTP/1.1 403 Forbidden\r\nContent-Length: 0\r\nConnection: close\r\n\r\n",true); }
        };
        $worker->onMessage=function(TcpConnection $connection,string $message):void {
            if(!$connection->context->elangAccepted) { $connection->close();return; }
            if(time()-$connection->context->elangWindow>=60) { $connection->context->elangWindow=time();$connection->context->elangMessages=0; }
            if(++$connection->context->elangMessages>60) { $connection->close();return; }
            try {
                if(!$this->tokens->verify($connection->context->elangToken)) { $connection->close();return; }
                $data=json_decode($message,true,32,JSON_THROW_ON_ERROR);if(!is_array($data)||!is_string($data['type']??null)) throw new \InvalidArgumentException('Send a JSON message with a type.');
                if($data['type']==='ping') { $connection->send('{"type":"pong"}');return; }
                if(!is_array($data['payload']??null)) throw new \InvalidArgumentException('Supply an object payload.');$claims=$connection->context->elangClaims;
                $user=$this->users->find($claims['user']);if(!$user) { $connection->close();return; }
                $result=$this->messages->handle($data['type'],$data['payload'],$user,$claims['channel'],$claims['commands']);$connection->send(json_encode(['type'=>'result','command'=>$data['type'],'data'=>$result],JSON_THROW_ON_ERROR));
            } catch(\Throwable) { $connection->send('{"type":"error","message":"The command was rejected. Check its name, permission, and payload."}'); }
        };
        $worker->onBufferFull=fn(TcpConnection $connection)=>$connection->close();
        $worker->onClose=function(TcpConnection $connection)use(&$connections):void { unset($connections[$connection->id]); };
        $worker->onWorkerStart=function()use(&$connections):void {
            $this->app->container->get(\Elang\Database\Database::class)->connection()->close();
            Timer::add(0.5,function()use(&$connections):void {
                foreach($connections as $connection) {
                    if(!$connection->context->elangAccepted) { if(time()>$connection->context->elangDeadline) $connection->close();continue; }
                    try {
                        if(time()-$connection->context->elangChecked>=5) { if(!$this->tokens->verify($connection->context->elangToken)) { $connection->close();continue; }$connection->context->elangChecked=time(); }
                        foreach($this->events->after($connection->context->elangClaims['channel'],$connection->context->elangCursor) as $event) { if($connection->send(json_encode(['type'=>'event']+$event,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false) { $connection->close();break; }$connection->context->elangCursor=$event['id']; }
                    }catch(\Throwable) { $connection->close(); }
                }
            });
        };
        $_SERVER['argv']=$GLOBALS['argv']=['elang-realtime','start'];$_SERVER['argc']=$GLOBALS['argc']=2;Worker::runAll();
    }
}
