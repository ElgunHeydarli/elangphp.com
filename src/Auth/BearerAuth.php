<?php
declare(strict_types=1);
namespace Elang\Auth;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\HttpException;
use Symfony\Component\HttpFoundation\{Request,Response};
final class BearerAuth implements Middleware
{
    public function __construct(private ApiTokens $tokens) {}
    public function process(Request $request,Closure $next): Response
    {
        $header=$request->headers->get('Authorization','');
        $token=str_starts_with($header,'Bearer ')?substr($header,7):'';
        $access=$this->tokens->authenticate($token);
        if(!$access) throw new HttpException(401,'A valid bearer token is required.',['WWW-Authenticate'=>'Bearer']);
        $request->attributes->set('_access_token',$access);$request->attributes->set('_user',$access->user);
        return $next($request);
    }
}
