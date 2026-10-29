<?php
declare(strict_types=1);
namespace App\Controllers;
use App\ContentRepository;
use Elang\{Application, View\View};
use Elang\Http\{HttpException, Response};
use Symfony\Component\HttpFoundation\{Request, Response as HttpResponse};
final class SiteController
{
    public function __construct(private View $view, private ContentRepository $content, private Application $app) {}
    private function page(string $template, string $title, string $description, string $active, string $path, array $data = []): HttpResponse
    {
        return $this->view->render($template, array_merge(['title' => $title, 'description' => $description, 'active' => $active, 'path' => $path, 'asset_version' => filemtime($this->app->basePath.'/public/assets/app.css').'-'.filemtime($this->app->basePath.'/public/assets/app.js')], $data));
    }
    public function home(): HttpResponse { return $this->page('home', 'Elang — Build with clarity.', 'An approachable PHP framework with explicit conventions, readable code, and room for your next idea.', 'home', '/'); }
    public function docs(string $slug): HttpResponse
    {
        $document = $this->content->document('docs/v0.1', $slug);
        return $this->page('document', $document['title'].' — Elang documentation', 'Learn '.$document['title'].' in Elang. Versioned documentation with working PHP examples.', 'docs', '/docs/v0.1/'.$slug, ['document' => $document, 'groups' => ContentRepository::GROUPS]);
    }
    public function markdown(string $slug): HttpResponse { return new HttpResponse($this->content->document('docs/v0.1', $slug)['markdown'], 200, ['Content-Type' => 'text/markdown; charset=utf-8']); }
    public function learn(): HttpResponse { return $this->page('learn', 'Learn Elang — One small step at a time.', 'Six practical lessons to take you from your first PHP route to a deployable Elang application.', 'learn', '/learn', ['lessons' => ContentRepository::LESSONS]); }
    public function lesson(string $slug): HttpResponse
    {
        $document = $this->content->document('learn', $slug);
        return $this->page('document', $document['title'].' — Learn Elang', 'A practical Elang lesson with complete code examples.', 'learn', '/learn/'.$slug, ['document' => $document, 'groups' => ['Learning path' => array_map(fn ($i) => $i[0], ContentRepository::LESSONS)]]);
    }
    public function examples(): HttpResponse { return $this->page('examples', 'Elang examples — Small by design.', 'Explore working Elang examples for routing, validation, and database queries.', 'examples', '/examples', ['examples' => ContentRepository::EXAMPLES]); }
    public function example(string $slug): HttpResponse
    {
        $document = $this->content->document('examples', $slug);
        return $this->page('document', $document['title'].' — Elang examples', 'A complete, focused Elang code example.', 'examples', '/examples/'.$slug, ['document' => $document, 'groups' => ['Examples' => array_map(fn ($i) => $i[0], ContentRepository::EXAMPLES)]]);
    }
    public function ecosystem(): HttpResponse
    {
        $installed = json_decode(file_get_contents($this->app->basePath.'/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
        $versions = array_column($installed['packages'], 'version', 'name');
        $packages = [
            ['symfony/http-foundation', 'HTTP', 'Requests, responses, cookies, and sessions.', 'https://symfony.com/doc/7.4/components/http_foundation.html'],
            ['symfony/routing', 'Routing', 'Named routes, parameters, requirements, and URL generation.', 'https://symfony.com/doc/7.4/routing.html'],
            ['doctrine/dbal', 'Database', 'Database connections, parameterized queries, and portable schemas.', 'https://www.doctrine-project.org/projects/dbal.html'],
            ['twig/twig', 'Templates', 'HTML templates with automatic escaping and inheritance.', 'https://twig.symfony.com/doc/3.x/'],
            ['symfony/validator', 'Validation', 'Composable constraints and useful field errors.', 'https://symfony.com/doc/7.4/validation.html'],
            ['symfony/console', 'Console', 'Clear commands for everyday development tasks.', 'https://symfony.com/doc/7.4/components/console.html'],
            ['symfony/cache', 'Cache', 'PSR-16 access to the filesystem cache.', 'https://symfony.com/doc/7.4/components/cache.html'],
            ['symfony/mailer', 'Mail', 'A transport-based mailer, ready for your SMTP service.', 'https://symfony.com/doc/7.4/mailer.html'],
            ['symfony/event-dispatcher', 'Events', 'Decouple application behavior with listeners.', 'https://symfony.com/doc/7.4/components/event_dispatcher.html'],
            ['monolog/monolog', 'Logging', 'Structured server-side logs through PSR-3.', 'https://github.com/Seldaek/monolog'],
            ['league/commonmark', 'Markdown', 'Standards-based Markdown for this documentation site.', 'https://commonmark.thephpleague.com/'],
            ['symfony/rate-limiter', 'Rate limiting', 'Shared filesystem-backed counters with process locks.', 'https://symfony.com/doc/7.4/rate_limiter.html'],
        ];
        return $this->page('ecosystem', 'Elang ecosystem — Good foundations matter.', 'Explore the established open-source packages behind Elang and the versions used by this site.', 'ecosystem', '/ecosystem', ['packages' => $packages, 'versions' => $versions]);
    }
    public function updates(): HttpResponse { return $this->page('updates', 'Elang updates — Built in the open.', 'Release notes, development milestones, and the next steps for Elang.', 'updates', '/updates'); }
    public function roadmap(): HttpResponse { return $this->page('roadmap', 'Elang roadmap — A clear direction.', 'See what works in the Elang development preview and what still needs to be built.', 'updates', '/roadmap'); }
    public function community(): HttpResponse { return $this->page('community', 'Elang community — Make it better, together.', 'Explore the Elang source, report reproducible issues, and contribute to the documentation.', 'community', '/community'); }
    public function reference(): HttpResponse { return $this->page('reference', 'Elang API reference', 'A concise reference for the public application, routing, container, database, and security APIs.', 'docs', '/api-reference'); }
    public function search(Request $request): HttpResponse
    {
        $query = $request->query->all()['q'] ?? '';
        if (!is_string($query) || mb_strlen($query) > 100) throw new HttpException(400, 'Search queries must be 100 characters or fewer.');
        return Response::json(['query' => $query, 'results' => $this->content->search($query)]);
    }
    public function sitemap(): HttpResponse
    {
        $base = htmlspecialchars(rtrim($this->app->config->get('url', 'https://elangphp.com'), '/'), ENT_XML1);
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($this->content->urls() as $url) $xml .= '<url><loc>'.$base.htmlspecialchars($url, ENT_XML1).'</loc></url>';
        return new HttpResponse($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
    public function feed(): HttpResponse
    {
        $base = htmlspecialchars(rtrim($this->app->config->get('url', 'https://elangphp.com'), '/'), ENT_XML1);
        return new HttpResponse('<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom"><title>Elang updates</title><id>'.$base.'/updates</id><link href="'.$base.'/updates"/><link href="'.$base.'/feed.xml" rel="self"/><updated>2026-10-29T08:00:00+04:00</updated><author><name>Elang</name></author><entry><title>Elang development preview</title><id>'.$base.'/updates#development-preview</id><link href="'.$base.'/updates#development-preview"/><updated>2026-10-29T08:00:00+04:00</updated><summary>The first development snapshot: a working HTTP kernel, modular services, and documentation built with Elang.</summary></entry></feed>', 200, ['Content-Type' => 'application/atom+xml; charset=utf-8']);
    }
    public function llms(): HttpResponse
    {
        $base = rtrim($this->app->config->get('url', 'https://elangphp.com'), '/');
        $text = "# Elang PHP framework\n\n> Elang is a PHP 8.3+ framework. It is not a programming language. Current status: 0.1 development preview; no stable release or Packagist publication yet. Do not invent supported APIs or performance claims.\n\nUse the versioned documentation below. HTTP uses Symfony HttpFoundation, validation uses Symfony Constraint objects, queries use Doctrine DBAL, and templates use Twig. There is no built-in ORM, authentication system, queue worker, or scheduler yet.\n\n## Documentation\n";
        foreach ($this->content->docs() as $slug => $title) $text .= '- ['.$title.']('.$base.'/docs/v0.1/'.$slug.'.md)'."\n";
        $text .= "\n## Source\n- [Repository](https://github.com/ElgunHeydarli/elangphp.com)\n- [API reference](".$base."/api-reference)\n";
        return new HttpResponse($text, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
