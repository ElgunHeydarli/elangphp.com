<?php
declare(strict_types=1);
namespace Elang\Auth;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\HttpException;
use Symfony\Component\HttpFoundation\{Request,Response};
final class RequireScope implements Middleware
{
    public function process(Request $request,Closure $next): Response
    {
        $token=$request->attributes->get('_access_token');$scope=$request->attributes->get('_scope');
        if(!is_string($scope)||$scope==='') throw new \LogicException('Protected API routes require an explicit _scope default.');
        if(!$token instanceof AccessToken||!$token->allows($scope)) throw new HttpException(403,'This token does not have the required scope.');
        return $next($request);
    }
}
