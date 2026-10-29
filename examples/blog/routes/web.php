<?php
declare(strict_types=1);
use App\Controllers\BlogController;
use Elang\Auth\{RequireAuth,RequireVerified};
use Elang\Http\Middleware\{SessionMiddleware,CsrfMiddleware,RateLimitMiddleware};
$router->group('',[RateLimitMiddleware::class,SessionMiddleware::class,CsrfMiddleware::class],function($router):void {
    $router->get('/',[BlogController::class,'index'],'home');$router->get('/posts/{id}',[BlogController::class,'show'])->where('id','[0-9]+');
    $router->group('',[RequireAuth::class,RequireVerified::class],function($router):void {
        $router->get('/posts/new',[BlogController::class,'create']);$router->post('/posts',[BlogController::class,'save']);
        $router->get('/posts/{id}/edit',[BlogController::class,'edit'])->where('id','[0-9]+');$router->post('/posts/{id}',[BlogController::class,'save'])->where('id','[0-9]+');$router->post('/posts/{id}/delete',[BlogController::class,'delete'])->where('id','[0-9]+');
    });
});
