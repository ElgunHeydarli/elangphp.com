<?php
declare(strict_types=1);
namespace Elang\Auth;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\HttpException;
use Symfony\Component\HttpFoundation\{Request,Response};
final class RequireAdmin implements Middleware
{
    public function process(Request $request,Closure $next): Response
    {
        $user=$request->attributes->get('_user');
        if(!$user instanceof User||!$user->verified||$user->role!=='admin') throw new HttpException(403,'Administrator access is required.');
        return $next($request);
    }
}
