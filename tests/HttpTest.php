<?php
declare(strict_types=1);
namespace Tests;
use Closure;
use Elang\Contracts\Middleware;
use Elang\Http\{Response, HttpException};
use Symfony\Component\HttpFoundation\{Request, Response as HttpResponse};
final class FirstMiddleware implements Middleware
{
    public function process(Request $request, Closure $next): HttpResponse { $request->attributes->set('steps', 'first>'); $response = $next($request); $response->headers->set('X-Order', $request->attributes->get('steps').'<first'); return $response; }
}
final class SecondMiddleware implements Middleware
{
    public function process(Request $request, Closure $next): HttpResponse { $request->attributes->set('steps', $request->attributes->get('steps').'second>'); $response = $next($request); $request->attributes->set('steps', $request->attributes->get('steps').'<second'); return $response; }
}
final class HttpTest extends TestCase
{
    public function testTypedRouteAndNamedUrl(): void
    {
        $app = $this->application(); $app->router->get('/posts/{id}', fn (int $id) => ['id' => $id], 'posts');
        $response = $app->handle(Request::create('/posts/42'));
        self::assertSame(200, $response->getStatusCode()); self::assertSame(['id' => 42], json_decode($response->getContent(), true));
        self::assertSame('/posts/7', $app->router->url('posts', ['id' => 7]));
        self::assertSame(400, $app->handle(Request::create('/posts/nope'))->getStatusCode());
    }
    public function testMethodAndUnknownRouteErrors(): void
    {
        $app = $this->application(); $app->router->get('/only-get', fn () => ['ok' => true]);
        $response = $app->handle(Request::create('/only-get', 'POST'));
        self::assertSame(405, $response->getStatusCode()); self::assertStringContainsString('GET', $response->headers->get('Allow'));
        self::assertSame(404, $app->handle(Request::create('/unknown'))->getStatusCode());
    }
    public function testHeadAndNullResponsesHaveEmptyBody(): void
    {
        $app = $this->application(); $app->router->get('/hello', fn () => ['message' => 'hello']); $app->router->get('/empty', fn () => null);
        self::assertSame('', $app->handle(Request::create('/hello', 'HEAD'))->getContent());
        self::assertSame(204, $app->handle(Request::create('/empty'))->getStatusCode());
    }
    public function testMiddlewareOrderWithNestedGroups(): void
    {
        $app = $this->application(); $app->middleware(FirstMiddleware::class);
        $app->router->group('/api', [SecondMiddleware::class], function ($router) { $router->group('/v1', [], fn ($router) => $router->get('/hello', fn () => ['ok' => true])); });
        self::assertSame('first>second><second<first', $app->handle(Request::create('/api/v1/hello'))->headers->get('X-Order'));
    }
    public function testProductionErrorsHideSecretsAndKeepSecurityHeaders(): void
    {
        $app = $this->application(); $app->router->get('/api/fail', fn () => throw new \RuntimeException('database-password-secret'));
        $response = $app->handle(Request::create('/api/fail'));
        self::assertSame(500, $response->getStatusCode()); self::assertStringNotContainsString('database-password-secret', $response->getContent());
        self::assertTrue($response->headers->has('X-Request-Id')); self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options')); self::assertTrue($response->headers->has('Content-Security-Policy'));
        self::assertStringContainsString('database-password-secret', file_get_contents($this->temporary.'/storage/logs/app.log'));
    }
    public function testBodySizeLimit(): void
    {
        $app = $this->application(['max_body_bytes' => 10]); $app->router->post('/api/data', fn () => ['ok' => true]);
        self::assertSame(413, $app->handle(Request::create('/api/data', 'POST', [], [], [], [], str_repeat('x', 11)))->getStatusCode());
    }
    public function testRequestBindingChangesAcrossRequests(): void
    {
        $app = $this->application(); $app->router->get('/echo', fn (Request $request) => ['q' => $request->query->get('q')]);
        self::assertSame('two', json_decode($app->handle(Request::create('/echo?q=two'))->getContent(), true)['q']);
        self::assertSame('three', json_decode($app->handle(Request::create('/echo?q=three'))->getContent(), true)['q']);
    }
    public function testDuplicateRouteNamesAreRejected(): void
    {
        $app = $this->application(); $app->router->get('/a', fn () => [], 'same');
        $this->expectException(\InvalidArgumentException::class); $app->router->get('/b', fn () => [], 'same');
    }
    public function testSafeLocalRedirect(): void { self::assertSame('/docs', Response::redirect('/docs')->getTargetUrl()); }
    #[\PHPUnit\Framework\Attributes\DataProvider('unsafeRedirects')]
    public function testUnsafeRedirectIsRejected(string $url): void { $this->expectException(\InvalidArgumentException::class); Response::redirect($url); }
    public static function unsafeRedirects(): array { return [['https://evil.example'], ['//evil.example'], ['/\\evil.example'], ["/path\r\nHeader: bad"], [' /path']]; }
}
