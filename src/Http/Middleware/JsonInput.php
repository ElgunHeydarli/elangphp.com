<?php
declare(strict_types=1);
namespace Elang\Http\Middleware;
use Closure;
use Elang\{Contracts\Middleware,Http\HttpException};
use Symfony\Component\HttpFoundation\{Request,Response};
final class JsonInput implements Middleware
{
    public function process(Request $request,Closure $next):Response
    {
        if(!$request->isMethodSafe()&&str_contains($request->headers->get('Content-Type',''),'application/json')) {
            if(strlen($request->getContent())>65536) throw new HttpException(413,'This JSON form is too large.');
            try { $value=json_decode($request->getContent(),false,32,JSON_THROW_ON_ERROR); }catch(\JsonException) { throw new HttpException(400,'Send valid JSON.'); }
            if(!$value instanceof \stdClass) throw new HttpException(400,'Send a JSON object.');$request->request->replace(json_decode($request->getContent(),true,32,JSON_THROW_ON_ERROR));
        }return $next($request);
    }
}
