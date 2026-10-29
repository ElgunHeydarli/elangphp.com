<?php
declare(strict_types=1);
namespace Elang\Auth;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\Response as Responses;
use Symfony\Component\HttpFoundation\{Request,Response};
final class RequireVerified implements Middleware
{
    public function process(Request $request,Closure $next): Response
    {
        $user=$request->attributes->get('_user');
        return $user instanceof User&&$user->verified?$next($request):Responses::redirect('/dashboard');
    }
}
