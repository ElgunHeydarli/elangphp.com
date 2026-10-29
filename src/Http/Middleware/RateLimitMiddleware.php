<?php
declare(strict_types=1);
namespace Elang\Http\Middleware;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\HttpException;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\RateLimiter\RateLimiterFactory;
final class RateLimitMiddleware implements Middleware
{
    public function __construct(private RateLimiterFactory $limiter) {}
    public function process(Request $request, Closure $next): Response
    {
        $limit = $this->limiter->create($request->getClientIp() ?? 'unknown')->consume();
        if (!$limit->isAccepted()) throw new HttpException(429, 'Too many requests. Please try again shortly.', ['Retry-After' => (string) max(1, $limit->getRetryAfter()->getTimestamp() - time())]);
        $response = $next($request);
        $response->headers->set('X-RateLimit-Remaining', (string) $limit->getRemainingTokens());
        return $response;
    }
}
