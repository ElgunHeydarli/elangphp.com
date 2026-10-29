<?php
declare(strict_types=1);
namespace Elang\Debug;
use Elang\Application;
use Elang\Http\HttpException;
use Elang\Security\Encryption;
use Elang\View\View;
use Symfony\Component\HttpFoundation\{Request,Response};
final class StudioController
{
    public function __construct(private Application $app,private View $view,private Collector $collector) {}
    private function guard(Request $request): void
    {
        $remote=$request->server->get('REMOTE_ADDR','');
        if(!$this->collector->enabled()||!in_array($remote,['127.0.0.1','::1'],true)||!in_array($request->getHost(),['localhost','127.0.0.1','::1','[::1]'],true)) throw new HttpException(404,'Page not found.');
    }
    private function page(Request $request,string $tab,array $data=[]): Response
    {
        $this->guard($request);
        $response=$this->view->render('@elang/studio',array_merge(['tab'=>$tab,'routes'=>[],'traces'=>[],'trace'=>null,'messages'=>[]],$data));
        $response->headers->set('Cache-Control','private, no-store');$response->headers->set('X-Robots-Tag','noindex');return $response;
    }
    public function index(Request $request): Response
    {
        $this->guard($request);$files=glob($this->app->config->get('paths.storage').'/studio/*.json')?:[];
        usort($files,fn($a,$b)=>filemtime($b)<=>filemtime($a));$traces=[];
        foreach(array_slice($files,0,100) as $file) $traces[]=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
        return $this->page($request,'requests',['traces'=>$traces]);
    }
    public function trace(Request $request,string $id): Response
    {
        $this->guard($request);if(!preg_match('/^[a-f0-9]{16}$/D',$id)) throw new HttpException(404,'Trace not found.');
        $path=$this->app->config->get('paths.storage').'/studio/'.$id.'.json';if(!is_file($path)) throw new HttpException(404,'Trace not found.');
        return $this->page($request,'trace',['trace'=>json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR)]);
    }
    public function routes(Request $request): Response
    {
        $this->guard($request);$routes=[];
        foreach($this->app->router->all() as $name=>$route) $routes[]=['name'=>$name,'path'=>$route->getPath(),'methods'=>implode('|',$route->getMethods()),'middleware'=>$route->getDefault('_middleware')??[]];
        return $this->page($request,'routes',['routes'=>$routes]);
    }
    public function mail(Request $request): Response
    {
        $this->guard($request);$messages=[];$files=glob($this->app->config->get('paths.storage').'/mailbox/*.mail')?:[];
        usort($files,fn($a,$b)=>filemtime($b)<=>filemtime($a));
        foreach(array_slice($files,0,50) as $file) {
            try { $messages[]=json_decode($this->app->container->get(Encryption::class)->decrypt(file_get_contents($file)),true,512,JSON_THROW_ON_ERROR); }catch(\UnexpectedValueException|\JsonException){}
        }
        return $this->page($request,'mail',['messages'=>$messages]);
    }
}
