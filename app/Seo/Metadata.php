<?php
declare(strict_types=1);
namespace App\Seo;
use App\ContentRepository;
use Elang\Application;
final class Metadata
{
    public function __construct(private Application $app, private ContentRepository $content, private \App\I18n\Locale $locale) {}
    public function build(string $title, string $description, string $path, string $active, ?array $document = null): array
    {
        $base = rtrim($this->app->config->get('url', 'https://elangphp.com'), '/');
        $url = $base.$path;
        $publisher = ['@type' => 'Organization', '@id' => $base.'/#project', 'name' => 'Elang', 'url' => $base, 'logo' => ['@type' => 'ImageObject', 'url' => $base.'/assets/favicon.svg']];
        $graph = [$publisher, ['@type' => 'WebSite', '@id' => $base.'/#website', 'name' => 'Elang PHP Framework', 'url' => $base.'/', 'inLanguage' => $this->locale->current(), 'publisher' => ['@id' => $base.'/#project']]];
        $page = ['@type' => $document ? 'TechArticle' : 'WebPage', '@id' => $url.'#page', 'url' => $url, 'name' => $title, 'description' => $description, 'inLanguage' => $this->locale->current(), 'isPartOf' => ['@id' => $base.'/#website'], 'about' => ['@id' => $base.'/#framework']];
        if ($document) {
            $page['headline'] = $document['title'];
            $page['author'] = ['@id' => $base.'/#project'];
            $page['datePublished'] = $document['published'] ?? '2026-10-29';
            $page['dateModified'] = $document['modified'];
            $page['proficiencyLevel'] = str_starts_with($document['section'], 'learn') ? 'Beginner' : 'Beginner to intermediate';
            $page['mainEntityOfPage'] = $url;
        }
        $graph[] = $page;
        $graph[] = ['@type' => 'SoftwareSourceCode', '@id' => $base.'/#framework', 'name' => 'Elang PHP Framework', 'url' => $base.'/', 'description' => 'An open-source PHP framework. Version 0.1 is a development preview with changing APIs.', 'programmingLanguage' => ['@type' => 'ComputerLanguage', 'name' => 'PHP'], 'runtimePlatform' => 'PHP 8.3+', 'version' => Application::VERSION, 'codeRepository' => 'https://github.com/ElgunHeydarli/elangphp.com', 'license' => 'https://opensource.org/license/mit'];
        $crumbs = [['name' => $this->locale->text('Home'), 'url' => $this->locale->path('/')]];
        if ($this->locale->logical($path) !== '/') {
            if ($document) $crumbs[] = ['name' => $this->locale->text(match ($active) { 'docs' => 'Documentation', 'learn' => 'Learn', 'guides' => 'Guides', 'updates'=>'Updates', default => 'Examples' }), 'url' => $this->locale->path($active === 'docs' ? '/docs/v0.1/introduction' : '/'.$active)];
            $crumbs[] = ['name' => $document['title'] ?? explode(' — ', $title)[0], 'url' => $path];
            $items = [];
            foreach ($crumbs as $index => $crumb) $items[] = ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $crumb['name'], 'item' => $base.$crumb['url']];
            $graph[] = ['@type' => 'BreadcrumbList', '@id' => $url.'#breadcrumbs', 'itemListElement' => $items];
        }
        return ['structured_data' => json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR), 'breadcrumbs' => $crumbs, 'modified' => $this->content->modified($path)];
    }
}
