<?php
declare(strict_types=1);
namespace App\I18n;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\HttpException;
use Symfony\Component\HttpFoundation\{Request,Response};
final class LocaleMiddleware implements Middleware
{
    public function __construct(private Locale $locale,private Html $html) {}
    public function process(Request $request,Closure $next): Response
    {
        $path=$request->getPathInfo();$code='en';
        if(preg_match('~^/(az|ru)(?=/|$)~',$path,$match)) { $code=$match[1];$logical=substr($path,strlen($match[0]))?:'/'; }
        else $logical=$path;
        if(str_starts_with($logical,'/api/search')) { $value=$request->query->all()['lang']??$code;if(!is_string($value)||!isset(Locale::LANGUAGES[$value])) throw new HttpException(400,'Unsupported language.');$code=$value; }
        $this->locale->set($code);$request->attributes->set('_logical_path',$logical);$request->attributes->set('_route_path',$logical);
        return $next($request);
    }
    public function response(\Elang\Http\ResponseEvent $event): void
    {
        $request=$event->request;$response=$event->response;
        $response->headers->set('Content-Language',$this->locale->current());
        $location=$response->headers->get('Location');if($location!==null) $response->headers->set('Location',$this->locale->path($location));
        if(str_contains($response->headers->get('Content-Type',''),'text/html')&&$response->getStatusCode()!==304) {
            $response->setContent($this->html->render($response->getContent(),$request));
            if($response->headers->has('ETag')) { $response->setEtag(hash('sha256',$response->getContent()));$response->isNotModified($request); }
        }
    }
}
