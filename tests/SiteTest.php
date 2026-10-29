<?php
declare(strict_types=1);
namespace Tests;
use App\ContentRepository;
use Symfony\Component\HttpFoundation\Request;
final class SiteTest extends TestCase
{
    private function site(): \Elang\Application
    {
        $app = new \Elang\Application(dirname(__DIR__), ['paths' => ['storage' => $this->temporary.'/storage'], 'database' => ['url' => 'sqlite:///:memory:'], 'url' => 'https://elangphp.com']);
        $app->provider(new \App\Providers\SiteProvider()); $router = $app->router; require dirname(__DIR__).'/routes/web.php'; return $app;
    }
    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function testPublicPageRenders(string $path): void
    {
        $response = $this->site()->handle(Request::create($path));
        self::assertSame(200, $response->getStatusCode(), $response->getContent()); self::assertStringContainsString('<html lang="en">', $response->getContent()); self::assertStringContainsString('rel="canonical"', $response->getContent());
    }
    public static function publicPages(): array
    {
        $paths = ['/', '/learn', '/examples', '/ecosystem', '/updates', '/roadmap', '/community', '/api-reference'];
        foreach (array_merge(...array_values(ContentRepository::GROUPS)) as $slug => $title) $paths[] = '/docs/v0.1/'.$slug;
        foreach (ContentRepository::LESSONS as $slug => $title) $paths[] = '/learn/'.$slug;
        foreach (ContentRepository::EXAMPLES as $slug => $title) $paths[] = '/examples/'.$slug;
        return array_map(fn ($path) => [$path], $paths);
    }
    public function testSearchAndQueryLimits(): void
    {
        $app = $this->site(); $response = $app->handle(Request::create('/api/search?q=routing'));
        self::assertSame(200, $response->getStatusCode()); self::assertNotEmpty(json_decode($response->getContent(), true)['results']);
        self::assertSame(400, $app->handle(Request::create('/api/search?q='.str_repeat('x', 101)))->getStatusCode());
        self::assertSame(400, $app->handle(Request::create('/api/search?q[]=bad'))->getStatusCode());
        self::assertSame(400, $app->handle(Request::create('/api/playground/hello?name[]=bad'))->getStatusCode());
    }
    public function testUnknownDocumentDoesNotReadArbitraryFiles(): void
    {
        $app = $this->site(); self::assertSame(404, $app->handle(Request::create('/docs/v0.1/not-real'))->getStatusCode());
        self::assertSame(404, $app->handle(Request::create('/docs/v0.1/..%2F..%2F.env'))->getStatusCode());
    }
    public function testMarkdownAndMachineReadableExports(): void
    {
        $app = $this->site();
        self::assertStringStartsWith('# Routing', $app->handle(Request::create('/docs/v0.1/routing.md'))->getContent());
        self::assertStringContainsString('/docs/v0.1/routing.md', $app->handle(Request::create('/llms.txt'))->getContent());
        self::assertStringContainsString('<urlset', $app->handle(Request::create('/sitemap.xml'))->getContent());
        self::assertStringContainsString('<feed', $app->handle(Request::create('/feed.xml'))->getContent());
    }
    public function testLivePlaygroundValidationAndDatabase(): void
    {
        $app = $this->site(); $app->container->get(\Elang\Database\Migrator::class)->migrate();
        $db = $app->handle(Request::create('/api/playground/database')); self::assertSame(3, count(json_decode($db->getContent(), true)['data']));
        $valid = $app->handle(Request::create('/api/playground/validate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"name":"Ada","email":"ada@example.com"}'));
        self::assertSame(200, $valid->getStatusCode());
        $invalid = $app->handle(Request::create('/api/playground/validate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"name":"Ada","email":"bad"}'));
        self::assertSame(422, $invalid->getStatusCode());
        self::assertSame(400, $app->handle(Request::create('/api/playground/validate', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{bad'))->getStatusCode());
    }
    public function testHtmlEscapesErrorMessages(): void
    {
        $app = $this->site(); $app->router->get('/unsafe-error', fn () => throw new \Elang\Http\HttpException(400, '<script>bad</script>'));
        self::assertStringNotContainsString('<script>bad</script>', $app->handle(Request::create('/unsafe-error'))->getContent());
    }
}
