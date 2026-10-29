<?php
declare(strict_types=1);
namespace App;
use Elang\Http\HttpException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
final class ContentRepository
{
    private MarkdownConverter $converter;
    public const GROUPS = [
        'Start here' => ['introduction' => 'Introduction', 'installation' => 'Installation', 'quickstart' => 'Your first application', 'configuration' => 'Configuration', 'structure' => 'Directory structure'],
        'Build your application' => ['routing' => 'Routing', 'controllers' => 'Controllers & responses', 'middleware' => 'Middleware', 'container' => 'Service container', 'views' => 'Views & templates', 'validation' => 'Validation'],
        'Go further' => ['database' => 'Database', 'migrations' => 'Migrations', 'security' => 'Security & sessions', 'cache' => 'Cache', 'events' => 'Events', 'mail' => 'Mail', 'console' => 'Console commands', 'testing' => 'Testing', 'deployment' => 'Deployment', 'ai' => 'Working with AI'],
    ];
    public const LESSONS = ['first-route' => ['Your first route', 'Start with a request. Finish with a response.', '01', '10 min'], 'json-api' => ['Build a JSON API', 'Route parameters, controllers, and clear responses.', '02', '15 min'], 'validate-input' => ['Validate user input', 'Accept the right data. Explain what went wrong.', '03', '15 min'], 'database' => ['Connect a database', 'Create a table and query real records.', '04', '20 min'], 'templates' => ['Render your first page', 'Turn data into a safely escaped HTML page.', '05', '15 min'], 'ship' => ['Get ready to deploy', 'Configuration, tests, and a public web root.', '06', '15 min']];
    public const EXAMPLES = ['hello-api' => ['Hello, JSON', 'A route, a parameter, and a JSON response.', 'HTTP'], 'validated-api' => ['A validated endpoint', 'Structured validation errors for a small API.', 'Validation'], 'database-query' => ['Read from a database', 'A parameterized query with Doctrine DBAL.', 'Database']];
    public function __construct(private string $directory)
    {
        $env = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false, 'heading_permalink' => ['symbol' => '#', 'insert' => 'after', 'id_prefix' => '', 'fragment_prefix' => '']]);
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new HeadingPermalinkExtension());
        $this->converter = new MarkdownConverter($env);
    }
    public function docs(): array { return array_merge(...array_values(self::GROUPS)); }
    public function document(string $section, string $slug): array
    {
        $registry = match ($section) { 'docs/v0.1' => $this->docs(), 'learn' => array_map(fn ($item) => $item[0], self::LESSONS), 'examples' => array_map(fn ($item) => $item[0], self::EXAMPLES), default => [] };
        if (!array_key_exists($slug, $registry)) throw new HttpException(404, 'This document does not exist.');
        $path = $this->directory.'/'.$section.'/'.$slug.'.md';
        if (!is_file($path)) throw new HttpException(404, 'This document is not available.');
        $markdown = file_get_contents($path);
        $html = (string) $this->converter->convert($markdown);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $toc = [];
        foreach ($dom->getElementsByTagName('h2') as $heading) {
            $anchor = $heading->getElementsByTagName('a')->item(0);
            $id = $heading->getAttribute('id') ?: ($anchor ? $anchor->getAttribute('id') : '');
            if ($id) $toc[] = ['id' => $id, 'title' => rtrim(trim($heading->textContent), '# ')];
        }
        $keys = array_keys($registry); $index = array_search($slug, $keys, true);
        $previous = $index > 0 ? $keys[$index - 1] : null;
        $next = $index < count($keys) - 1 ? $keys[$index + 1] : null;
        return ['title' => $registry[$slug], 'slug' => $slug, 'section' => $section, 'markdown' => $markdown, 'html' => $html, 'toc' => $toc, 'previous' => $previous ? ['url' => '/'.$section.'/'.$previous, 'title' => $registry[$previous]] : null, 'next' => $next ? ['url' => '/'.$section.'/'.$next, 'title' => $registry[$next]] : null];
    }
    public function search(string $query): array
    {
        $query = mb_strtolower(trim($query));
        if (mb_strlen($query) < 2) return [];
        $results = [];
        foreach (['docs/v0.1' => $this->docs(), 'learn' => array_map(fn ($i) => $i[0], self::LESSONS), 'examples' => array_map(fn ($i) => $i[0], self::EXAMPLES)] as $section => $registry) {
            foreach ($registry as $slug => $title) {
                $text = file_get_contents($this->directory.'/'.$section.'/'.$slug.'.md');
                $position = mb_stripos($text, $query);
                $titleMatch = mb_stripos($title, $query) !== false;
                if ($position === false && !$titleMatch) continue;
                $snippet = mb_substr($text, max(0, (int) $position - 45), 150);
                $results[] = ['title' => $title, 'url' => '/'.$section.'/'.$slug, 'section' => str_starts_with($section, 'docs') ? 'Documentation' : ucfirst($section), 'excerpt' => trim(preg_replace('/[#`*\[\]>]/u', '', $snippet)), 'score' => $titleMatch ? 2 : 1];
            }
        }
        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($results, 0, 15);
    }
    public function urls(): array
    {
        $urls = ['/', '/learn', '/examples', '/ecosystem', '/updates', '/roadmap', '/community', '/api-reference'];
        foreach (array_keys($this->docs()) as $slug) $urls[] = '/docs/v0.1/'.$slug;
        foreach (array_keys(self::LESSONS) as $slug) $urls[] = '/learn/'.$slug;
        foreach (array_keys(self::EXAMPLES) as $slug) $urls[] = '/examples/'.$slug;
        return $urls;
    }
}
