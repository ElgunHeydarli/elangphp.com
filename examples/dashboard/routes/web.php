<?php
declare(strict_types=1);
use Elang\Auth\RequireAuth;
use Elang\Http\Middleware\{SessionMiddleware,CsrfMiddleware,RateLimitMiddleware};
use App\Controllers\WorkspaceController;
$router->get('/',fn()=>Elang\Http\Response::redirect('/workspace'),'home');
$router->group('/workspace',[RateLimitMiddleware::class,SessionMiddleware::class,CsrfMiddleware::class,RequireAuth::class],function($router):void {
    $router->get('',[WorkspaceController::class,'index']);$router->post('/notes',[WorkspaceController::class,'create']);$router->post('/notes/{id}/toggle',[WorkspaceController::class,'toggle'])->where('id','[0-9]+');$router->post('/notes/{id}/delete',[WorkspaceController::class,'delete'])->where('id','[0-9]+');
});
