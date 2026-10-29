<?php
declare(strict_types=1);
namespace App;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\Response as Responses;
use Symfony\Component\HttpFoundation\{Request, Response};
final class CanonicalMiddleware implements Middleware
{
    public function __construct(private \Elang\Support\Config $config) {}
    public function process(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();
        $base=rtrim($this->config->get('url','https://elangphp.com'),'/');$host=parse_url($base,PHP_URL_HOST);
        if($host&&$request->getHost()==='www.'.$host&&in_array($request->getMethod(),['GET','HEAD'],true)) return new \Symfony\Component\HttpFoundation\RedirectResponse($base.$path.($request->getQueryString()?'?'.$request->getQueryString():''),301);
        $logical=$request->attributes->get('_logical_path',$path);
        if ($path !== '/' && str_ends_with($path, '/') && !str_starts_with($path, '//') && in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            $query = $request->getQueryString();
            return Responses::redirect(rtrim($path, '/').($query ? '?'.$query : ''), 301);
        }
        $response = $next($request);
        if (str_starts_with($logical, '/api/') || str_ends_with($logical, '.md') || str_starts_with($logical, '/demo/') || str_starts_with($logical,'/admin')) $response->headers->set('X-Robots-Tag', 'noindex, follow');
        return $response;
    }
}
