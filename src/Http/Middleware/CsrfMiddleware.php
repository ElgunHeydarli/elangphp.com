<?php
declare(strict_types=1);
namespace Elang\Http\Middleware;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\HttpException;
use Elang\Security\Csrf;
use Symfony\Component\HttpFoundation\{Request, Response};
final class CsrfMiddleware implements Middleware
{
    public function __construct(private Csrf $csrf) {}
    public function process(Request $request, Closure $next): Response
    {
        if (!in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $token = $request->headers->get('X-CSRF-Token') ?? $request->request->get('_token');
            if (!is_string($token) || !$this->csrf->verify($request, $token)) throw new HttpException(419, 'Your form expired. Refresh the page and try again.');
        }
        return $next($request);
    }
}
