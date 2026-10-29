<?php
declare(strict_types=1);
namespace Tests;
use App\ContentRepository;
use Symfony\Component\HttpFoundation\Request;
final class SeoTest extends TestCase
{
    private function site(): \Elang\Application
    {
        $app = new \Elang\Application(dirname(__DIR__), ['paths' => ['storage' => $this->temporary.'/storage'], 'url' => 'https://elangphp.com', 'database' => ['url' => 'sqlite:///:memory:']]);
        $app->provider(new \App\Providers\SiteProvider()); $router = $app->router; require dirname(__DIR__).'/routes/web.php'; return $app;
    }
    public function testEveryIndexablePageHasDistinctMetadataAndValidGraph(): void
    {
        $app = $this->site(); $titles = []; $descriptions = [];
        foreach ($app->container->get(ContentRepository::class)->urls() as $path) {
            $response = $app->handle(Request::create($path)); self::assertSame(200, $response->getStatusCode(), $path);
            $dom = new \DOMDocument(); @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
            self::assertSame(1, $dom->getElementsByTagName('h1')->length, $path);
            $title = $dom->getElementsByTagName('title')->item(0)->textContent;
            self::assertNotContains($title, $titles, 'Duplicate title at '.$path); $titles[] = $title;
            $description = $xpath->query('//meta[@name="description"]')->item(0)->getAttribute('content');
            self::assertGreaterThan(45, mb_strlen($description), $path); self::assertNotContains($description, $descriptions, 'Duplicate description at '.$path); $descriptions[] = $description;
            self::assertSame('https://elangphp.com'.$path, $xpath->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
            $data = json_decode($xpath->query('//script[@type="application/ld+json"]')->item(0)->textContent, true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('https://schema.org', $data['@context']);
            self::assertContains('WebSite', array_column($data['@graph'], '@type'));
            self::assertContains('SoftwareSourceCode', array_column($data['@graph'], '@type'));
            if ($path !== '/') self::assertContains('BreadcrumbList', array_column($data['@graph'], '@type'));
            self::assertStringNotContainsString('aggregateRating', $response->getContent());
        }
    }
    public function testSitemapContainsCanonicalPublicPagesAndRealModifiedDates(): void
    {
        $app = $this->site(); $dom = new \DOMDocument();
        self::assertTrue($dom->loadXML($app->handle(Request::create('/sitemap.xml'))->getContent()));
        $urls = array_map(fn ($node) => $node->textContent, iterator_to_array($dom->getElementsByTagName('loc')));
        self::assertCount(count($app->container->get(ContentRepository::class)->urls())*3, $urls); self::assertSame($urls, array_values(array_unique($urls)));
        self::assertSame(count($urls), $dom->getElementsByTagName('lastmod')->length);
        foreach ($urls as $url) { self::assertStringStartsWith('https://elangphp.com/', $url); self::assertStringNotContainsString('/demo/', $url); self::assertStringNotContainsString('/api/', $url); }
    }
    public function testCanonicalPathsAndConditionalRequests(): void
    {
        $app = $this->site();$www=$app->handle(Request::create('https://www.elangphp.com/ru/learn?q=test'));self::assertSame(301,$www->getStatusCode());self::assertSame('https://elangphp.com/ru/learn?q=test',$www->headers->get('Location')); $redirect = $app->handle(Request::create('/learn/'));
        self::assertSame(301, $redirect->getStatusCode()); self::assertSame('/learn', $redirect->headers->get('Location'));
        $first = $app->handle(Request::create('/learn')); $request = Request::create('/learn'); $request->headers->set('If-None-Match', $first->headers->get('ETag'));
        $cached = $app->handle($request); self::assertSame(304, $cached->getStatusCode()); self::assertSame('', $cached->getContent());
    }
    public function testDuplicateRepresentationsAreNoindex(): void
    {
        $app = $this->site();
        self::assertSame('noindex, follow', $app->handle(Request::create('/api/health'))->headers->get('X-Robots-Tag'));
        self::assertSame('noindex, follow', $app->handle(Request::create('/docs/v0.1/routing.md'))->headers->get('X-Robots-Tag'));
    }
    public function testStructuredDataCannotEscapeItsScriptElement(): void
    {
        $app = $this->site(); $metadata = $app->container->get(\App\Seo\Metadata::class)->build('</script><script>alert(1)</script>', 'A safe description', '/', 'home');
        self::assertStringNotContainsString('</script>', $metadata['structured_data']);
        self::assertIsArray(json_decode($metadata['structured_data'], true, 512, JSON_THROW_ON_ERROR));
    }
    public function testComparisonTableAndCompleteGuideRender(): void
    {
        $response = $this->site()->handle(Request::create('/guides/elang-vs-laravel'));
        self::assertStringContainsString('<table>', $response->getContent()); self::assertStringContainsString('Laravel', $response->getContent());
    }
}
