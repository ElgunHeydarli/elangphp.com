<?php
declare(strict_types=1);
use App\Controllers\{SiteController, PlaygroundController};
use Elang\Http\Response;
use Elang\Http\Middleware\RateLimitMiddleware;
$router->get('/', [SiteController::class, 'home'], 'home');
$router->get('/docs', fn () => Response::redirect('/docs/v0.1/introduction'), 'docs');
$router->get('/docs/v0.1', fn () => Response::redirect('/docs/v0.1/introduction'));
$router->get('/docs/v0.1/{slug}.md', [SiteController::class, 'markdown'], 'docs.markdown')->where('slug', '[a-z0-9-]+');
$router->get('/docs/v0.1/{slug}', [SiteController::class, 'docs'], 'docs.page')->where('slug', '[a-z0-9-]+');
$router->get('/learn', [SiteController::class, 'learn'], 'learn');
$router->get('/learn/{slug}', [SiteController::class, 'lesson'])->where('slug', '[a-z0-9-]+');
$router->get('/examples', [SiteController::class, 'examples'], 'examples');
$router->get('/examples/{slug}', [SiteController::class, 'example'])->where('slug', '[a-z0-9-]+');
foreach (['ecosystem', 'updates', 'roadmap', 'community'] as $page) $router->get('/'.$page, [SiteController::class, $page], $page);
$router->get('/api-reference', [SiteController::class, 'reference'], 'reference');
$router->get('/sitemap.xml', [SiteController::class, 'sitemap']);
$router->get('/feed.xml', [SiteController::class, 'feed']);
$router->get('/llms.txt', [SiteController::class, 'llms']);
$router->group('/api', [RateLimitMiddleware::class], function ($router) {
    $router->get('/search', [SiteController::class, 'search']);
    $router->get('/playground/hello', [PlaygroundController::class, 'hello']);
    $router->get('/playground/database', [PlaygroundController::class, 'database']);
    $router->post('/playground/validate', [PlaygroundController::class, 'validate']);
    $router->get('/health', fn () => ['status' => 'ok', 'framework' => 'Elang', 'version' => Elang\Application::VERSION]);
});
