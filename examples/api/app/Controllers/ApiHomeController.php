<?php
declare(strict_types=1);
namespace App\Controllers;
use Elang\Auth\Auth;
use Elang\Security\Csrf;
use Elang\View\View;
use Symfony\Component\HttpFoundation\{Request,Response};
final class ApiHomeController
{
    public function __construct(private Auth $auth,private View $view,private Csrf $csrf) {}
    public function __invoke(Request $request): Response
    {
        $user=$this->auth->user($request);return $this->view->render('api-home',['title'=>'Your protected Notes API','user'=>$user,'token'=>$user?$this->csrf->token($request):'']);
    }
}
