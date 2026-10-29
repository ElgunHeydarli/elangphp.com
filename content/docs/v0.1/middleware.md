# Middleware

Middleware runs around the next stage of a request. Implement the Elang Middleware interface.

## Create middleware

```php
namespace App;

use Closure;
use Elang\Contracts\Middleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AddHeader implements Middleware
{
    public function process(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Application', 'My Elang app');
        return $response;
    }
}
```

## Register middleware

Use `$app->middleware(AddHeader::class)` in your bootstrap for global middleware, or `->middleware(AddHeader::class)` on a route. Registration order is request order; responses unwind in reverse order.

## Sessions and CSRF

```php
use Elang\Http\Middleware\SessionMiddleware;
use Elang\Http\Middleware\CsrfMiddleware;

$app->middleware(SessionMiddleware::class, CsrfMiddleware::class);
```

Session middleware must run before CSRF middleware. Unsafe methods then require a matching session token in `_token` or the `X-CSRF-Token` header. Stateless API endpoints need their own authentication design; CSRF protection alone does not authenticate a caller.

## Rate limiting

Attach `RateLimitMiddleware` to public API groups. It uses a shared filesystem cache and process locks on one host. Multi-server deployments need a shared limiter store and lock backend.
