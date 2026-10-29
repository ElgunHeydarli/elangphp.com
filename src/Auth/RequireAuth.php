<?php
declare(strict_types=1);
namespace Elang\Auth;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\Response as Responses;
use Symfony\Component\HttpFoundation\{Request,Response};
final class RequireAuth implements Middleware
{
    public function __construct(private Auth $auth) {}
    public function process(Request $request,Closure $next): Response
    {
        $user=$this->auth->user($request);
        if(!$user) { if($request->isMethod('GET')) $request->getSession()->set('_auth_intended',$request->getPathInfo());return Responses::redirect('/login'); }
        $request->attributes->set('_user',$user);
        if($user->mustChangePassword&&!in_array($request->attributes->get('_route_path',$request->getPathInfo()),['/password','/logout'],true)) return Responses::redirect('/password');
        return $next($request);
    }
}
