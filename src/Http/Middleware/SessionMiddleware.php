<?php
declare(strict_types=1);
namespace Elang\Http\Middleware;
use Closure;
use Elang\Contracts\Middleware;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Elang\Support\Config;
final class SessionMiddleware implements Middleware
{
    public function __construct(private Config $config) {}
    public function process(Request $request, Closure $next): Response
    {
        if (!$request->hasSession()) {
            $request->setSession(new Session(new NativeSessionStorage([
                'name' => 'elang_session', 'cookie_httponly' => true, 'cookie_samesite' => 'lax',
                'cookie_secure' => $request->isSecure(), 'use_strict_mode' => true,
            ], new NativeFileSessionHandler($this->config->get('paths.storage').'/sessions'))));
        }
        try { return $next($request); }
        finally { if ($request->getSession()->isStarted()) $request->getSession()->save(); }
    }
}
