<?php
declare(strict_types=1);
namespace Elang\Realtime;
use Elang\Http\HttpException;
use Symfony\Component\HttpFoundation\{Request,StreamedResponse};
final class Stream
{
    public function __construct(private Events $events) {}
    public static function frame(array $event):string { return 'id: '.$event['id']."\n".'event: '.$event['event']."\n".'data: '.json_encode($event['data'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n\n"; }
    public function response(Request $request,string $authorizedChannel,int $seconds=20,?\Closure $stillAuthorized=null):StreamedResponse
    {
        if($seconds<1||$seconds>25) throw new \InvalidArgumentException('SSE streams must reconnect within 1–25 seconds.');
        $value=$request->headers->get('Last-Event-ID',$request->query->all()['after']??'0');
        if(!is_scalar($value)||!ctype_digit((string)$value)||strlen((string)$value)>15) throw new HttpException(400,'Invalid replay cursor.');$cursor=(int)$value;
        if($request->hasSession()&&$request->getSession()->isStarted()) $request->getSession()->save();
        return new StreamedResponse(function()use($authorizedChannel,$seconds,$stillAuthorized,$cursor):void {
            $deadline=microtime(true)+$seconds;$heartbeat=0.0;echo "retry: 1500\n\n";
            while(microtime(true)<$deadline&&!connection_aborted()) {
                if($stillAuthorized&&!$stillAuthorized()) break;
                foreach($this->events->after($authorizedChannel,$cursor) as $event) { echo self::frame($event);$cursor=$event['id']; }
                if(microtime(true)-$heartbeat>5) { echo ": heartbeat\n\n";$heartbeat=microtime(true); }
                if(ob_get_level()>0) @ob_flush();flush();usleep(500000);
            }
        },200,['Content-Type'=>'text/event-stream; charset=utf-8','Cache-Control'=>'private, no-store','X-Accel-Buffering'=>'no','X-Robots-Tag'=>'noindex']);
    }
}
