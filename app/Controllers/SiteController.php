<?php
declare(strict_types=1);
namespace App\Controllers;
use App\ContentRepository;
use Elang\{Application, View\View};
use Elang\Http\{HttpException, Response};
use Symfony\Component\HttpFoundation\{Request, Response as HttpResponse};
final class SiteController
{
    public function __construct(private View $view, private ContentRepository $content, private Application $app, private \App\Seo\Metadata $seo, private \App\I18n\Locale $locale) {}
    private function page(string $template, string $title, string $description, string $active, string $path, array $data = []): HttpResponse
    {
        $logical=$path;$title=$this->locale->text($title);$description=$this->locale->text($description);$path=$this->locale->path($path);
        $alternates=[];$available=$this->content->languages($logical);foreach($available as $language) $alternates[$language]=$this->locale->path($logical,$language);
        $this->app->container->get(Request::class)->attributes->set('_available_languages',$available);
        $metadata = $this->seo->build($title, $description, $path, $active, $data['document'] ?? null);
        $tracking=(new \App\Seo\PublicAnalytics($this->app->config))->settings();
        $response = $this->view->render($template, array_merge($metadata, $tracking, ['site_locale'=>$this->locale->current(),'alternates'=>$alternates,'title' => $title, 'description' => $description, 'active' => $active, 'path' => $path, 'asset_version' => filemtime($this->app->basePath.'/public/assets/app.css').'-'.filemtime($this->app->basePath.'/public/assets/app.js').'-'.filemtime($this->app->basePath.'/public/assets/analytics.js')], $data));
        if($tracking['google_measurement_id']!=='') $response->headers->set('Content-Security-Policy',\App\Seo\PublicAnalytics::contentSecurityPolicy());
        $response->setPublic();
        $response->setMaxAge(0);
        $response->setLastModified(new \DateTimeImmutable($metadata['modified']));
        $response->setEtag(hash('sha256', $response->getContent()));
        return $response;
    }
    public function home(): HttpResponse { return $this->page('home', 'Elang PHP Framework — Build with Clarity', 'An approachable PHP framework for authenticated APIs, real-time applications, background jobs, and React or Next.js backends with clear documentation.', 'home', '/'); }
    public function privacy(): HttpResponse { return $this->page('privacy','Privacy & analytics — Elang','How Elang handles optional analytics, essential account data, and your privacy choices.','privacy','/privacy'); }
    public function docs(string $slug): HttpResponse
    {
        $document = $this->content->document('docs/v0.1', $slug);
        return $this->page('document', $document['title'].' — Elang PHP Documentation', $document['description'], 'docs', '/docs/v0.1/'.$slug, ['document' => $document, 'groups' => $this->content->navigation()]);
    }
    public function markdown(string $slug): HttpResponse { return new HttpResponse($this->content->document('docs/v0.1', $slug)['markdown'], 200, ['Content-Type' => 'text/markdown; charset=utf-8']); }
    public function learn(): HttpResponse { return $this->page('learn', 'Learn PHP with Elang — A Practical Beginner Learning Path', 'Learn PHP and Elang step by step: environment setup, PHP basics, routes, validation, database queries, and a complete task board project.', 'learn', '/learn', ['lessons' => $this->content->hub('learn')]); }
    public function lesson(string $slug): HttpResponse
    {
        $document = $this->content->document('learn', $slug);
        return $this->page('document', $document['title'].' — Learn Elang', $document['description'], 'learn', '/learn/'.$slug, ['document' => $document, 'groups' => ['Learning path' => $this->content->catalog()['learn']]]);
    }
    public function examples(): HttpResponse { return $this->page('examples', 'Elang examples — Small by design.', 'Explore working Elang examples for routing, validation, and database queries.', 'examples', '/examples', ['examples' => $this->content->hub('examples')]); }
    public function example(string $slug): HttpResponse
    {
        $document = $this->content->document('examples', $slug);
        return $this->page('document', $document['title'].' — Elang examples', $document['description'], 'examples', '/examples/'.$slug, ['document' => $document, 'groups' => ['Examples' => $this->content->catalog()['examples']]]);
    }
    public function guides(): HttpResponse
    {
        return $this->page('guides', 'PHP Framework Guides — Elang Concepts & Practical Tutorials', 'Understand PHP frameworks, compare Elang and Laravel, and build a working REST API with detailed, practical guides.', 'guides', '/guides', ['guides' => $this->content->hub('guides')]);
    }
    public function guide(string $slug): HttpResponse
    {
        $document = $this->content->document('guides', $slug);
        return $this->page('document', $document['title'].' — Elang PHP Guides', $document['description'], 'guides', '/guides/'.$slug, ['document' => $document, 'groups' => ['Practical guides' => $this->content->catalog()['guides']]]);
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
    public function updates(): HttpResponse { return $this->page('updates', 'Elang updates — Built in the open.', 'Release notes, development milestones, and the next steps for Elang.', 'updates', '/updates',['posts'=>$this->content->hub('updates')]); }
    public function update(string $slug): HttpResponse { $document=$this->content->document('updates',$slug);return $this->page('document',$document['title'].' — Elang updates',$document['description'],'updates','/updates/'.$slug,['document'=>$document,'groups'=>['Updates'=>$this->content->catalog()['updates']]]); }
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
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
        foreach(['en','az','ru'] as $locale) foreach($this->content->urls($locale) as $url) {
            $xml.='<url><loc>'.$base.htmlspecialchars($this->locale->path($url,$locale),ENT_XML1).'</loc><lastmod>'.$this->content->modified($url,$locale).'</lastmod>';
            foreach($this->content->languages($url) as $language) $xml.='<xhtml:link rel="alternate" hreflang="'.$language.'" href="'.$base.htmlspecialchars($this->locale->path($url,$language),ENT_XML1).'"/>';
            if(in_array('en',$this->content->languages($url),true)) $xml.='<xhtml:link rel="alternate" hreflang="x-default" href="'.$base.htmlspecialchars($url,ENT_XML1).'"/>';
            $xml.='</url>';
        }
        return new HttpResponse($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
    public function feed(): HttpResponse
    {
        $base=rtrim($this->app->config->get('url','https://elangphp.com'),'/');$language=$this->locale->current();$feedPath=($language==='en'?'':'/'.$language).'/feed.xml';$updates=$base.$this->locale->path('/updates');
        $entries=[['title'=>$this->locale->text('Development preview'),'url'=>$updates.'#development-preview','updated'=>'2026-10-29T04:00:00+00:00','description'=>$this->locale->text('The first Elang development snapshot brings the application kernel, modular services, and a documentation website running on Elang itself.')]];
        foreach(array_slice($this->content->catalog()['updates'],0,100,true) as $slug=>$title) {
            $document=$this->content->document('updates',$slug);$path='/updates/'.$slug;$entries[]=['title'=>$document['title'],'url'=>$base.$this->locale->path($path),'updated'=>$this->content->modified($path),'description'=>$document['description']];
        }
        usort($entries,fn($a,$b)=>strcmp($b['updated'],$a['updated']));$escape=fn(string $value)=>htmlspecialchars($value,ENT_XML1|ENT_QUOTES,'UTF-8');
        $xml='<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom" xml:lang="'.$language.'"><title>Elang — '.$escape($this->locale->text('Updates')).'</title><id>'.$escape($updates).'</id><link href="'.$escape($updates).'"/><link href="'.$escape($base.$feedPath).'" rel="self"/><updated>'.$escape($entries[0]['updated']).'</updated><author><name>Elang</name></author>';
        foreach($entries as $entry) $xml.='<entry><title>'.$escape($entry['title']).'</title><id>'.$escape($entry['url']).'</id><link href="'.$escape($entry['url']).'"/><updated>'.$escape($entry['updated']).'</updated><summary>'.$escape($entry['description']).'</summary></entry>';
        return new HttpResponse($xml.'</feed>',200,['Content-Type'=>'application/atom+xml; charset=utf-8']);
    }
    public function llms(): HttpResponse
    {
        $base = rtrim($this->app->config->get('url', 'https://elangphp.com'), '/');
        $text = "# Elang PHP framework\n\n> Elang is a PHP 8.3+ framework. It is not a programming language. Current status: 0.1 development preview; development packages are indexed on Packagist as dev-main (alias 0.1.x-dev); no stable release. Do not invent supported APIs or performance claims.\n\nUse the versioned documentation below. HTTP uses Symfony HttpFoundation, validation uses Symfony Constraint objects, queries use Doctrine DBAL, and templates use Twig. Elang includes an explicit model repository, account authentication, scoped API tokens, local Studio diagnostics, and application starters. Database-backed JSON jobs, UTC scheduling, private SSE/WebSocket channels, frontend CORS, and idempotency for JSON writes are included. Distributed broker adapters and a full ORM are not included.\n\n## Documentation\n";
        foreach ($this->content->docs() as $slug => $title) $text .= '- ['.$title.']('.$base.'/docs/v0.1/'.$slug.'.md)'."\n";
        $text .= "\n## Source\n- [Repository](https://github.com/ElgunHeydarli/elangphp.com)\n- [API reference](".$base."/api-reference)\n";
        $text .= "\n## Terminal installation\nUse Composer create-project with repository ".$base."/packages, package elang/elang, and version ".Application::VERSION.". The elang/installer Composer package provides elang new after one-time setup. No manual source extraction is needed.\n\n## Beginner learning path\n";
        foreach (ContentRepository::LESSONS as $slug => $lesson) $text .= '- ['.$lesson[0].']('.$base.'/learn/'.$slug.")\n";
        $text .= "\n## Practical guides\n";
        foreach (ContentRepository::GUIDES as $slug => $guide) $text .= '- ['.$guide[0].']('.$base.'/guides/'.$slug.")\n";
        return new HttpResponse($text, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
