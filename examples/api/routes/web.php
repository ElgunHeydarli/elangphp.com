<?php
declare(strict_types=1);
use Elang\Auth\{BearerAuth,RequireScope};
use Elang\Http\Middleware\{SessionMiddleware,RateLimitMiddleware};
use App\Controllers\{ApiHomeController,NotesApiController};
$router->get('/',ApiHomeController::class,'home')->middleware(SessionMiddleware::class);
$router->group('/api',[RateLimitMiddleware::class,SessionMiddleware::class,\Elang\Auth\ApiIdentity::class,RequireScope::class,\Elang\Http\Idempotency\Idempotency::class],function($router):void {
    $router->get('/notes/events',[\App\Controllers\RealtimeController::class,'events'])->defaults(['_scope'=>'notes:read']);
    $router->post('/realtime-token',[\App\Controllers\RealtimeController::class,'token'])->defaults(['_scope'=>'notes:read']);
    $router->get('/notes',[NotesApiController::class,'index'])->defaults(['_scope'=>'notes:read']);
    $router->get('/notes/{id}',[NotesApiController::class,'show'])->where('id','[0-9]+')->defaults(['_scope'=>'notes:read']);
    $router->post('/notes',[NotesApiController::class,'create'])->defaults(['_scope'=>'notes:write']);
    $router->put('/notes/{id}',[NotesApiController::class,'update'])->where('id','[0-9]+')->defaults(['_scope'=>'notes:write']);
    $router->delete('/notes/{id}',[NotesApiController::class,'delete'])->where('id','[0-9]+')->defaults(['_scope'=>'notes:write']);
});

$router->get('/openapi.json',fn()=>\Elang\Http\Response::json(require dirname(__DIR__).'/resources/openapi.php'));
