<?php
declare(strict_types=1);
namespace App;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\Response as Responses;
use Symfony\Component\HttpFoundation\{Request, Response};
final class CanonicalMiddleware implements Middleware
{
    public function process(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();
        if ($path !== '/' && str_ends_with($path, '/') && !str_starts_with($path, '//') && in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            $query = $request->getQueryString();
            return Responses::redirect(rtrim($path, '/').($query ? '?'.$query : ''), 301);
        }
        $response = $next($request);
        if (str_starts_with($path, '/api/') || str_ends_with($path, '.md') || str_starts_with($path, '/demo/')) $response->headers->set('X-Robots-Tag', 'noindex, follow');
        return $response;
    }
}
