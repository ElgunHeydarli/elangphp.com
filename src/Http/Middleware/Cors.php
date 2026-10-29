<?php
declare(strict_types=1);
namespace Elang\Http\Middleware;
use Closure;
use Elang\{Contracts\Middleware,Support\Config,Http\HttpException};
use Symfony\Component\HttpFoundation\{Request,Response};
final class Cors implements Middleware
{
    private const METHODS=['GET','HEAD','POST','PUT','PATCH','DELETE','OPTIONS'];
    private const HEADERS=['authorization','content-type','idempotency-key','last-event-id','accept','x-csrf-token'];
    public function __construct(private Config $config) {}
    public function process(Request $request,Closure $next):Response
    {
        $request->attributes->remove('_elang_cors_origin');
        $origin=$request->headers->get('Origin');if($origin===null||$origin===$request->getSchemeAndHttpHost()) return $next($request);
        $allowed=$this->config->get('cors.origins',[]);
        if(!is_array($allowed)||!in_array($origin,$allowed,true)||$origin==='null'||!preg_match('~^https?://[^/]+$~D',$origin)) throw new HttpException(403,'This frontend origin is not allowed.');
        $request->attributes->set('_elang_cors_origin',$origin);
        if($request->isMethod('OPTIONS')&&$request->headers->has('Access-Control-Request-Method')) {
            if(!in_array(strtoupper($request->headers->get('Access-Control-Request-Method')),self::METHODS,true)) throw new HttpException(403,'This CORS method is not allowed.');
            $headers=array_filter(array_map('trim',explode(',',strtolower($request->headers->get('Access-Control-Request-Headers','')))));
            if(array_diff($headers,self::HEADERS)) throw new HttpException(403,'A requested CORS header is not allowed.');
            return new Response('',204,['Access-Control-Allow-Methods'=>implode(', ',self::METHODS),'Access-Control-Allow-Headers'=>implode(', ',self::HEADERS),'Access-Control-Max-Age'=>'600','Vary'=>'Origin, Access-Control-Request-Method, Access-Control-Request-Headers']);
        }
        return $next($request);
    }
    public static function apply(Request $request,Response $response):void
    {
        $origin=$request->attributes->get('_elang_cors_origin');if(!is_string($origin)) return;
        $response->headers->set('Access-Control-Allow-Origin',$origin);$response->headers->set('Access-Control-Expose-Headers','Location, X-Request-Id, Idempotency-Replayed');$response->setVary('Origin',false);
    }
}
