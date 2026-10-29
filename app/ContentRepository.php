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
    private array $staticCatalog=[];
    public const GROUPS = [
        'Start here' => ['introduction' => 'Introduction', 'requirements' => 'Requirements & setup', 'installation' => 'Installation', 'quickstart' => 'Your first application', 'configuration' => 'Configuration', 'structure' => 'Directory structure'],
        'Build your application' => ['routing' => 'Routing', 'controllers' => 'Controllers & responses', 'middleware' => 'Middleware', 'container' => 'Service container', 'views' => 'Views & templates', 'validation' => 'Validation'],
        'Build real projects' => ['account-security'=>'Account security','redis'=>'Redis infrastructure','tenancy'=>'Teams & tenant isolation','files'=>'Private files & images','notifications'=>'Notifications','observability'=>'Operations & telemetry','fullstack-starters'=>'Fullstack starters','saas'=>'SaaS workspace','releases'=>'Releases & upgrades','queues'=>'Jobs & scheduling','realtime'=>'Real-time & WebSockets','frontend'=>'React & Next.js integration','authentication'=>'Authentication & accounts','models'=>'Models & pagination','studio'=>'Elang Studio','content-management'=>'Content management'],
        'Go further' => ['database' => 'Database', 'migrations' => 'Migrations', 'security' => 'Security & sessions', 'cache' => 'Cache', 'events' => 'Events', 'mail' => 'Mail', 'console' => 'Console commands', 'testing' => 'Testing', 'deployment' => 'Deployment', 'ai' => 'Working with AI'],
        'Find an answer' => ['troubleshooting' => 'Troubleshooting', 'glossary' => 'PHP & framework glossary', 'faq' => 'Frequently asked questions'],
    ];
    public const LESSONS = ['environment' => ['Set up your environment', 'Install PHP and Composer, then verify every requirement.', '01', '20 min'], 'php-basics' => ['The PHP you need', 'Variables, arrays, functions, classes, and namespaces.', '02', '25 min'], 'first-route' => ['Your first route', 'Start with a request. Finish with a response.', '03', '10 min'], 'json-api' => ['Build a JSON API', 'Route parameters, controllers, and clear responses.', '04', '15 min'], 'validate-input' => ['Validate user input', 'Accept the right data. Explain what went wrong.', '05', '15 min'], 'database' => ['Connect a database', 'Create a table and query real records.', '06', '20 min'], 'templates' => ['Render your first page', 'Turn data into a safely escaped HTML page.', '07', '15 min'], 'build-a-task-board' => ['Build a complete task board', 'Forms, validation, sessions, and CSRF in one working project.', '08', '45 min'], 'debugging' => ['Find and fix a problem', 'Read status codes, logs, and the doctor report.', '09', '15 min'], 'ship' => ['Get ready to deploy', 'Configuration, tests, and a public web root.', '10', '15 min']];
    public const GUIDES = ['large-applications'=>['Design a large application with Elang','Modules, transactions, background jobs, real-time transports, deployment, and workload validation.'],'build-a-blog'=>['Build a database-backed blog','A complete authenticated publication with verified authors and ownership checks.'],'build-a-private-dashboard'=>['Build a private user workspace','Persistent notes, account settings, and ownership-scoped data.'],'build-a-protected-api'=>['Build a protected Notes API','Scoped bearer tokens, validation, pagination, and record ownership.'],'what-is-a-php-framework' => ['What is a PHP framework?', 'Understand requests, routes, controllers, and the problems a framework solves.'], 'elang-vs-laravel' => ['Elang vs Laravel', 'An honest comparison of current features, intended use, and project maturity.'], 'build-a-php-rest-api' => ['Build a PHP REST API with Elang', 'A complete, runnable JSON API with validation and useful error responses.']];
    public const EXAMPLES = ['hello-api' => ['Hello, JSON', 'A route, a parameter, and a JSON response.', 'HTTP'], 'validated-api' => ['A validated endpoint', 'Structured validation errors for a small API.', 'Validation'], 'database-query' => ['Read from a database', 'A parameterized query with Doctrine DBAL.', 'Database']];
    public function __construct(private string $directory, private ?\App\Cms\ContentStore $store = null, private ?\App\I18n\Locale $locale = null)
    {
        $env = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false, 'heading_permalink' => ['symbol' => '#', 'insert' => 'after', 'id_prefix' => '', 'fragment_prefix' => '']]);
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new HeadingPermalinkExtension());
        $env->addExtension(new TableExtension());
        $this->converter = new MarkdownConverter($env);
    }
    public function catalog(?string $locale = null): array
    {
        $locale ??= $this->locale?->current() ?? 'en';
        if(!isset($this->staticCatalog[$locale])) {
        $catalog=['docs/v0.1'=>array_merge(...array_values(self::GROUPS)),'learn'=>array_map(fn($item)=>$item[0],self::LESSONS),'examples'=>array_map(fn($item)=>$item[0],self::EXAMPLES),'guides'=>array_map(fn($item)=>$item[0],self::GUIDES),'updates'=>[]];
        foreach($catalog as $section=>$items) foreach($items as $slug=>$title) {
            $file=$this->directory.($locale==='en'?'':'/i18n/'.$locale).'/'.$section.'/'.$slug.'.md';
            if(!is_file($file)) { unset($catalog[$section][$slug]);continue; }
            if($locale!=='en'&&preg_match('/^# (.+)$/m',file_get_contents($file),$match)) $catalog[$section][$slug]=trim($match[1]);
        }
        $this->staticCatalog[$locale]=$catalog;
        }
        $catalog=$this->staticCatalog[$locale];
        if($this->store) foreach($catalog as $section=>$items) foreach($this->store->published($section,$locale) as $page) $catalog[$section][$page['slug']]=$page['title'];
        return $catalog;
    }
    public function docs(): array { return $this->catalog()['docs/v0.1']; }
    public function navigation(): array
    {
        $groups=self::GROUPS;$known=array_merge(...array_values($groups));$docs=$this->docs();
        foreach($groups as &$items) { foreach($items as $slug=>&$title) { if(isset($docs[$slug])) $title=$docs[$slug];else unset($items[$slug]); } }
        unset($items,$title);$extra=array_diff_key($docs,$known);if($extra) $groups['More documentation']=$extra;return $groups;
    }
    public function render(string $markdown): string { return (string)$this->converter->convert($markdown); }
    public function document(string $section, string $slug, ?string $locale = null): array
    {
        $locale ??= $this->locale?->current() ?? 'en';
        $registry = $this->catalog($locale)[$section] ?? [];
        if (!array_key_exists($slug, $registry)) throw new HttpException(404, 'This document does not exist.');
        $path = $this->directory.($locale==='en'?'':'/i18n/'.$locale).'/'.$section.'/'.$slug.'.md';
        $overlay=$this->store?->publishedPage($section,$slug,$locale);
        if ($overlay && $overlay['markdown']===null) $overlay=null;
        if (!$overlay && !is_file($path)) throw new HttpException(404, 'This document is not available.');
        $markdown = $overlay ? $overlay['markdown'] : file_get_contents($path);
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
        return ['source_path'=>$overlay?null:'content/'.($locale==='en'?'':'i18n/'.$locale.'/').$section.'/'.$slug.'.md','published' => $overlay ? gmdate('Y-m-d',(int)$overlay['published_at']) : '2026-10-29', 'description' => $overlay ? $overlay['description'] : $this->description($section, $slug, $markdown, $locale), 'reading_minutes' => max(1, (int) ceil(str_word_count(strip_tags($html)) / 180)), 'modified' => gmdate('Y-m-d', $overlay ? (int)$overlay['published_modified_at'] : filemtime($path)), 'title' => $registry[$slug], 'slug' => $slug, 'section' => $section, 'markdown' => $markdown, 'html' => $html, 'toc' => $toc, 'previous' => $previous ? ['url' => '/'.$section.'/'.$previous, 'title' => $registry[$previous]] : null, 'next' => $next ? ['url' => '/'.$section.'/'.$next, 'title' => $registry[$next]] : null];
    }
    public function description(string $section, string $slug, string $markdown, string $locale = 'en'): string
    {
        if ($locale === 'en' && $section === 'learn' && $slug === 'database') return 'Create your first SQLite table in Elang, apply a complete migration, insert a record, and return real database rows from a PHP route.';
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
        if ($locale === 'en' && isset($descriptions[$slug])) return $descriptions[$slug];
        $paragraphs = preg_split('/\n\s*\n/', trim($markdown));
        $text = $paragraphs[1] ?? $paragraphs[0];
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        return mb_substr(trim(preg_replace('/\s+/', ' ', str_replace(['#', '*', '`'], '', $text))), 0, 160);
    }
    public function modified(string $url, ?string $locale = null): string
    {
        $locale ??= $this->locale?->current() ?? 'en';
        if(preg_match('~^/(az|ru)(?=/|$)~',$url,$prefix)) { $locale=$prefix[1];$url=substr($url,strlen($prefix[0]))?:'/'; }
        if (preg_match('~^/(docs/v0\.1|learn|examples|guides|updates)/([a-z0-9-]+)$~', $url, $matches)) {
            $overlay=$this->store?->publishedPage($matches[1],$matches[2],$locale);
            if($overlay && $overlay['markdown']!==null) return gmdate('c',(int)$overlay['published_modified_at']);
            $file = $this->directory.($locale==='en'?'':'/i18n/'.$locale).'/'.$matches[1].'/'.$matches[2].'.md';
        }
        else $file = dirname($this->directory).'/resources/views/'.match ($url) { '/' => 'home', '/api-reference' => 'reference', default => trim($url, '/') }.'.twig';
        return gmdate('c', is_file($file) ? filemtime($file) : filemtime($this->directory));
    }
    public function search(string $query): array
    {
        $query = mb_strtolower(trim($query));
        if (mb_strlen($query) < 2) return [];
        $results = [];
        foreach ($this->catalog() as $section => $registry) {
            foreach ($registry as $slug => $title) {
                $text = $this->document($section,$slug)['markdown'];
                $position = mb_stripos($text, $query);
                $titleMatch = mb_stripos($title, $query) !== false;
                if ($position === false && !$titleMatch) continue;
                $snippet = mb_substr($text, max(0, (int) $position - 45), 150);
                $results[] = ['title' => $title, 'url' => $this->locale?->path('/'.$section.'/'.$slug) ?? '/'.$section.'/'.$slug, 'section' => $this->locale?->text(str_starts_with($section, 'docs') ? 'Documentation' : ucfirst($section)) ?? (str_starts_with($section, 'docs') ? 'Documentation' : ucfirst($section)), 'excerpt' => trim(preg_replace('/[#`*\[\]>]/u', '', $snippet)), 'score' => $titleMatch ? 2 : 1];
            }
        }
        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($results, 0, 15);
    }
    public function urls(?string $locale = null): array
    {
        $urls = ['/', '/learn', '/examples', '/ecosystem', '/updates', '/roadmap', '/community', '/api-reference', '/guides', '/privacy'];
        foreach ($this->catalog($locale) as $section=>$items) foreach(array_keys($items) as $slug) $urls[]='/'.$section.'/'.$slug;
        return $urls;
    }
    public function languages(string $path): array
    {
        $languages=[];foreach(['en','az','ru'] as $locale) if(in_array($path,$this->urls($locale),true)) $languages[]=$locale;return $languages;
    }
    public function hub(string $section): array
    {
        $items=[];foreach($this->catalog()[$section]??[] as $slug=>$title) { $doc=$this->document($section,$slug);$items[$slug]=[$title,$doc['description']];if($section==='examples') $items[$slug][]=self::EXAMPLES[$slug][2]??'Example';if($section==='learn') $items[$slug]=array_merge($items[$slug],array_slice(self::LESSONS[$slug]??['','','','10 min'],2)); }
        return $items;
    }
}
