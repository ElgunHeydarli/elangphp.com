<?php
declare(strict_types=1);
namespace Elang\Tenancy;
use Closure;
use Elang\{Contracts\Middleware,Auth\User,Http\HttpException};
use Symfony\Component\HttpFoundation\{Request,Response};
/** Place before idempotency/cache middleware; domain writes still recheck membership within their transaction. */
final class AuthorizeTeam implements Middleware
{
    public function __construct(private Teams $teams) {}
    public function process(Request $request,Closure $next):Response
    {
        $team=$request->attributes->get('team');if($team===null) return $next($request);$user=$request->attributes->get('_user');if(!$user instanceof User||!is_string($team)) throw new HttpException(403,'A team identity is required.');$permission=$request->attributes->get('_team_permission',$request->isMethodSafe()?'read':'write');if(!is_string($permission)) throw new \LogicException('Use an explicit team permission.');$this->teams->within($user,$team,$permission,fn()=>null);return $next($request);
    }
}
