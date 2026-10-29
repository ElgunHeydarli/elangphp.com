<?php
declare(strict_types=1);
namespace Tests;
use Elang\Validation\{Validator, ValidationException};
use Symfony\Component\Validator\Constraints as Assert;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Elang\Http\Middleware\RateLimitMiddleware;
use Symfony\Component\HttpFoundation\Request;
final class ServicesTest extends TestCase
{
    public function testValidationReturnsOnlyAllowedFields(): void
    {
        self::assertSame(['name' => 'Ada'], (new Validator())->validate(['name' => 'Ada', 'is_admin' => true], ['name' => [new Assert\NotBlank()]]));
    }
    public function testInvalidDataProducesStructuredErrors(): void
    {
        try { (new Validator())->validate(['email' => 'bad'], ['email' => [new Assert\NotBlank(), new Assert\Email()]]); self::fail('Expected validation error.'); }
        catch (ValidationException $e) { self::assertSame(422, $e->status); self::assertArrayHasKey('email', $e->errors); }
    }
    public function testCacheRoundTripAndExpiry(): void
    {
        $cache = $this->application()->container->get(CacheInterface::class);
        self::assertTrue($cache->set('greeting', ['hello' => 'world'], 60)); self::assertSame(['hello' => 'world'], $cache->get('greeting')); $cache->delete('greeting'); self::assertNull($cache->get('greeting'));
        $cache->set('expired', 'no', -1); self::assertSame('missing', $cache->get('expired', 'missing'));
    }
    public function testEventsAreSharedAndDispatchSynchronously(): void
    {
        $app = $this->application(); $events = $app->container->get(EventDispatcherInterface::class); $seen = [];
        $events->addListener('greeting', function ($event) use (&$seen) { $seen[] = $event->message; });
        $app->container->get(EventDispatcherInterface::class)->dispatch((object) ['message' => 'hello'], 'greeting'); self::assertSame(['hello'], $seen);
    }
    public function testRateLimitReturns429WithRetryHeader(): void
    {
        $app = $this->application(['rate_limit' => 1]); $app->router->get('/api/limited', fn () => ['ok' => true])->middleware(RateLimitMiddleware::class);
        self::assertSame(200, $app->handle(Request::create('/api/limited'))->getStatusCode());
        $response = $app->handle(Request::create('/api/limited')); self::assertSame(429, $response->getStatusCode()); self::assertGreaterThan(0, (int) $response->headers->get('Retry-After'));
    }
    public function testTwigEscapesUntrustedValues(): void
    {
        mkdir($this->temporary.'/resources/views', 0770, true); file_put_contents($this->temporary.'/resources/views/test.twig', '<h1>{{ name }}</h1>');
        $response = $this->application()->container->get(\Elang\View\View::class)->render('test', ['name' => '<script>alert(1)</script>']);
        self::assertStringContainsString('&lt;script&gt;', $response->getContent()); self::assertStringNotContainsString('<script>', $response->getContent());
    }
}
