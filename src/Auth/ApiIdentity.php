<?php
declare(strict_types=1);
namespace Elang\Auth;
use Closure;
use Elang\{Contracts\Middleware,Http\HttpException,Security\Csrf};
use Symfony\Component\HttpFoundation\{Request,Response};
final class ApiIdentity implements Middleware
{
    public function __construct(private Auth $auth,private ApiTokens $tokens,private Csrf $csrf) {}
    public function process(Request $request,Closure $next):Response
    {
        if($request->headers->has('Authorization')) {
            $header=$request->headers->get('Authorization','');$access=$this->tokens->authenticate(str_starts_with($header,'Bearer ')?substr($header,7):'');if(!$access) throw new HttpException(401,'A valid bearer token is required.',['WWW-Authenticate'=>'Bearer']);$request->attributes->set('_access_token',$access);$user=$access->user;
        } else {
            $user=$this->auth->user($request);if(!$user) throw new HttpException(401,'Sign in to continue.');
            if(!$request->isMethodSafe()&&!$this->csrf->verify($request,$request->headers->get('X-CSRF-Token',''))) throw new HttpException(419,'Your session form expired. Refresh and try again.');$request->attributes->set('_auth_session',true);
        }
        if(!$user->verified||$user->mustChangePassword) throw new HttpException(403,'Verify your email and update any temporary password first.');$request->attributes->set('_user',$user);$response=$next($request);$response->headers->set('Cache-Control','private, no-store');return $response;
    }
}
