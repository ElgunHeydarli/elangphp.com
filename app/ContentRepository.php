<?php
declare(strict_types=1);
namespace App;
use Elang\Http\HttpException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Extension\Table\TableExtension;
final class ContentRepository
{
    private MarkdownConverter $converter;
    public const GROUPS = [
        'Start here' => ['introduction' => 'Introduction', 'requirements' => 'Requirements & setup', 'installation' => 'Installation', 'quickstart' => 'Your first application', 'configuration' => 'Configuration', 'structure' => 'Directory structure'],
        'Build your application' => ['routing' => 'Routing', 'controllers' => 'Controllers & responses', 'middleware' => 'Middleware', 'container' => 'Service container', 'views' => 'Views & templates', 'validation' => 'Validation'],
        'Go further' => ['database' => 'Database', 'migrations' => 'Migrations', 'security' => 'Security & sessions', 'cache' => 'Cache', 'events' => 'Events', 'mail' => 'Mail', 'console' => 'Console commands', 'testing' => 'Testing', 'deployment' => 'Deployment', 'ai' => 'Working with AI'],
        'Find an answer' => ['troubleshooting' => 'Troubleshooting', 'glossary' => 'PHP & framework glossary', 'faq' => 'Frequently asked questions'],
    ];
    public const LESSONS = ['environment' => ['Set up your environment', 'Install PHP and Composer, then verify every requirement.', '01', '20 min'], 'php-basics' => ['The PHP you need', 'Variables, arrays, functions, classes, and namespaces.', '02', '25 min'], 'first-route' => ['Your first route', 'Start with a request. Finish with a response.', '03', '10 min'], 'json-api' => ['Build a JSON API', 'Route parameters, controllers, and clear responses.', '04', '15 min'], 'validate-input' => ['Validate user input', 'Accept the right data. Explain what went wrong.', '05', '15 min'], 'database' => ['Connect a database', 'Create a table and query real records.', '06', '20 min'], 'templates' => ['Render your first page', 'Turn data into a safely escaped HTML page.', '07', '15 min'], 'build-a-task-board' => ['Build a complete task board', 'Forms, validation, sessions, and CSRF in one working project.', '08', '45 min'], 'debugging' => ['Find and fix a problem', 'Read status codes, logs, and the doctor report.', '09', '15 min'], 'ship' => ['Get ready to deploy', 'Configuration, tests, and a public web root.', '10', '15 min']];
    public const GUIDES = ['what-is-a-php-framework' => ['What is a PHP framework?', 'Understand requests, routes, controllers, and the problems a framework solves.'], 'elang-vs-laravel' => ['Elang vs Laravel', 'An honest comparison of current features, intended use, and project maturity.'], 'build-a-php-rest-api' => ['Build a PHP REST API with Elang', 'A complete, runnable JSON API with validation and useful error responses.']];
    public const EXAMPLES = ['hello-api' => ['Hello, JSON', 'A route, a parameter, and a JSON response.', 'HTTP'], 'validated-api' => ['A validated endpoint', 'Structured validation errors for a small API.', 'Validation'], 'database-query' => ['Read from a database', 'A parameterized query with Doctrine DBAL.', 'Database']];
    public function __construct(private string $directory)
    {
        $env = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false, 'heading_permalink' => ['symbol' => '#', 'insert' => 'after', 'id_prefix' => '', 'fragment_prefix' => '']]);
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new HeadingPermalinkExtension());
        $env->addExtension(new TableExtension());
        $this->converter = new MarkdownConverter($env);
    }
    public function docs(): array { return array_merge(...array_values(self::GROUPS)); }
    public function document(string $section, string $slug): array
    {
        $registry = match ($section) { 'docs/v0.1' => $this->docs(), 'learn' => array_map(fn ($item) => $item[0], self::LESSONS), 'examples' => array_map(fn ($item) => $item[0], self::EXAMPLES), 'guides' => array_map(fn ($item) => $item[0], self::GUIDES), default => [] };
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
        return ['description' => $this->description($section, $slug, $markdown), 'reading_minutes' => max(1, (int) ceil(str_word_count(strip_tags($html)) / 180)), 'modified' => gmdate('Y-m-d', filemtime($path)), 'title' => $registry[$slug], 'slug' => $slug, 'section' => $section, 'markdown' => $markdown, 'html' => $html, 'toc' => $toc, 'previous' => $previous ? ['url' => '/'.$section.'/'.$previous, 'title' => $registry[$previous]] : null, 'next' => $next ? ['url' => '/'.$section.'/'.$next, 'title' => $registry[$next]] : null];
    }
    public function description(string $section, string $slug, string $markdown): string
    {
        if ($section === 'learn' && $slug === 'database') return 'Create your first SQLite table in Elang, apply a complete migration, insert a record, and return real database rows from a PHP route.';
        $descriptions = [
            'introduction' => 'Meet Elang, an open-source PHP framework with explicit routing, constructor injection, Twig views, and a practical learning path.',
            'requirements' => 'Check PHP 8.3+, required extensions, Composer, storage permissions, and your database with the Elang doctor command.',
            'installation' => 'Install Elang from your terminal with Composer, set up the global elang new command, and create your first PHP application or task board.',
            'routing' => 'Define named PHP routes, constrain URL parameters, create route groups, attach middleware, and generate links with Elang.',
            'database' => 'Connect Elang to SQLite, MySQL, or PostgreSQL using Doctrine DBAL. Learn bound parameters, queries, writes, and transactions.',
            'security' => 'Use Elang sessions, CSRF middleware, Argon2id password hashing, Sodium encryption, and safe response defaults in your PHP app.',
            'validation' => 'Validate PHP input with Symfony constraints in Elang. Return only approved fields and handle structured 422 validation errors.',
            'troubleshooting' => 'Fix common Elang setup and runtime errors: PHP versions, missing extensions, keys, permissions, routes, sessions, and databases.',
            'glossary' => 'Learn the PHP framework terms used in Elang: requests, routes, controllers, middleware, dependency injection, Composer, and CSRF.',
            'faq' => 'Answers about Elang installation, PHP requirements, Composer packages, hosting, release status, security, and learning the framework.',
            'environment' => 'Set up PHP and Composer on Windows, macOS, or Ubuntu. Verify your terminal, extensions, and Elang environment before writing code.',
            'php-basics' => 'Learn the PHP syntax used by Elang: variables, arrays, functions, typed parameters, classes, namespaces, and Composer autoloading.',
            'build-a-task-board' => 'Build and run a complete Elang task board with HTML forms, validation, session storage, CSRF protection, and a downloadable example.',
            'elang-vs-laravel' => 'Compare Elang and Laravel honestly: current capabilities, maturity, learning experience, deployment, and limitations of the Elang preview.',
            'build-a-php-rest-api' => 'Build a working PHP REST API with Elang, including JSON requests, route parameters, input validation, and explicit HTTP status codes.',
        ];
        if (isset($descriptions[$slug])) return $descriptions[$slug];
        $paragraphs = preg_split('/\n\s*\n/', trim($markdown));
        $text = $paragraphs[1] ?? $paragraphs[0];
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        return mb_substr(trim(preg_replace('/\s+/', ' ', str_replace(['#', '*', '`'], '', $text))), 0, 160);
    }
    public function modified(string $url): string
    {
        if (preg_match('~^/(docs/v0\.1|learn|examples|guides)/([a-z0-9-]+)$~', $url, $matches)) $file = $this->directory.'/'.$matches[1].'/'.$matches[2].'.md';
        else $file = dirname($this->directory).'/resources/views/'.match ($url) { '/' => 'home', '/api-reference' => 'reference', default => trim($url, '/') }.'.twig';
        return gmdate('c', is_file($file) ? filemtime($file) : filemtime($this->directory));
    }
    public function search(string $query): array
    {
        $query = mb_strtolower(trim($query));
        if (mb_strlen($query) < 2) return [];
        $results = [];
        foreach (['docs/v0.1' => $this->docs(), 'learn' => array_map(fn ($i) => $i[0], self::LESSONS), 'examples' => array_map(fn ($i) => $i[0], self::EXAMPLES), 'guides' => array_map(fn ($i) => $i[0], self::GUIDES)] as $section => $registry) {
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
        $urls = ['/', '/learn', '/examples', '/ecosystem', '/updates', '/roadmap', '/community', '/api-reference', '/guides'];
        foreach (array_keys($this->docs()) as $slug) $urls[] = '/docs/v0.1/'.$slug;
        foreach (array_keys(self::LESSONS) as $slug) $urls[] = '/learn/'.$slug;
        foreach (array_keys(self::EXAMPLES) as $slug) $urls[] = '/examples/'.$slug;
        foreach (array_keys(self::GUIDES) as $slug) $urls[] = '/guides/'.$slug;
        return $urls;
    }
}
