<?php
declare(strict_types=1);
namespace Elang\Observability;
use Elang\{Application,Container\Container,Contracts\ServiceProvider,Auth\RequireAuth,Auth\RequireAdmin,Http\Middleware\SessionMiddleware,Http\Middleware\CsrfMiddleware,Http\Middleware\RateLimitMiddleware};
final class Provider implements ServiceProvider
{
    public function register(Container $container):void {}
    public function boot(Application $app):void
    {
        $app->router->group('/operations',[RateLimitMiddleware::class,SessionMiddleware::class,RequireAuth::class,RequireAdmin::class,CsrfMiddleware::class],function($r):void { $r->get('',[Controller::class,'index']);$r->get('/metrics',[Controller::class,'metrics']);$r->post('/jobs/{id}/retry',[Controller::class,'retry'])->where('id','[a-f0-9]{32}'); });
    }
}
