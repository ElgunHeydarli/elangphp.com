<?php
declare(strict_types=1);
use Elang\Auth\Auth;
use Elang\Security\Csrf;
use Elang\View\View;
use Elang\Http\Middleware\SessionMiddleware;
$router->get('/',function(Symfony\Component\HttpFoundation\Request $request,Auth $auth,Csrf $csrf,View $view){
    $user=$auth->user($request);return $view->render('welcome',['title'=>'A clear beginning','user'=>$user,'token'=>$user?$csrf->token($request):'']);
},'home')->middleware(SessionMiddleware::class);
