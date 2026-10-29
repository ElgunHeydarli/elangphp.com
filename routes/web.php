<?php
declare(strict_types=1);
use App\Controllers\{SiteController, PlaygroundController};
use Elang\Http\Response;
use Elang\Http\Middleware\RateLimitMiddleware;
$router->get('/', [SiteController::class, 'home'], 'home');
$router->get('/privacy', [SiteController::class, 'privacy'], 'privacy');
$router->get('/docs', fn () => Response::redirect('/docs/v0.1/introduction'), 'docs');
$router->get('/docs/v0.1', fn () => Response::redirect('/docs/v0.1/introduction'));
$router->get('/docs/v0.1/{slug}.md', [SiteController::class, 'markdown'], 'docs.markdown')->where('slug', '[a-z0-9-]+');
$router->get('/docs/v0.1/{slug}', [SiteController::class, 'docs'], 'docs.page')->where('slug', '[a-z0-9-]+');
$router->get('/learn', [SiteController::class, 'learn'], 'learn');
$router->get('/learn/{slug}', [SiteController::class, 'lesson'])->where('slug', '[a-z0-9-]+');
$router->get('/guides', [SiteController::class, 'guides'], 'guides');
$router->get('/guides/{slug}', [SiteController::class, 'guide'])->where('slug', '[a-z0-9-]+');
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

$router->group('/demo/task-board', [RateLimitMiddleware::class, \Elang\Http\Middleware\SessionMiddleware::class, \Elang\Http\Middleware\CsrfMiddleware::class], function ($router) {
    $router->get('', [\App\Controllers\TaskBoardController::class, 'index']);
    $router->post('/add', [\App\Controllers\TaskBoardController::class, 'store']);
    $router->post('/toggle/{id}', [\App\Controllers\TaskBoardController::class, 'toggle'])->where('id', '[a-f0-9]{16}');
    $router->post('/delete/{id}', [\App\Controllers\TaskBoardController::class, 'delete'])->where('id', '[a-f0-9]{16}');
});
$router->get('/packages', [\App\Controllers\PackageController::class, 'index'], 'packages');
$router->get('/packages/packages.json', [\App\Controllers\PackageController::class, 'index']);
$router->get('/packages/dist/{file}', [\App\Controllers\PackageController::class, 'dist'])->where('file', '[a-z0-9.-]+');

$router->group('/admin',[RateLimitMiddleware::class,\Elang\Http\Middleware\JsonInput::class,\Elang\Http\Middleware\SessionMiddleware::class,\Elang\Http\Middleware\CsrfMiddleware::class,\Elang\Auth\RequireAuth::class,\Elang\Auth\RequireAdmin::class],function($router): void {
    $integration=\App\Controllers\IntegrationsController::class;$router->get('/integrations',[$integration,'index']);$router->post('/integrations/packagist',[$integration,'save']);$router->post('/integrations/packagist/publish',[$integration,'publish']);$router->post('/integrations/mail-dns',[$integration,'publishMailDns']);
    $controller=\App\Controllers\AdminController::class;
    $router->get('',[$controller,'index']);$router->get('/edit',[$controller,'edit']);$router->post('/save',[$controller,'save']);$router->post('/restore',[$controller,'restore']);
});
$router->get('/updates/{slug}',[SiteController::class,'update'])->where('slug','[a-z0-9-]+');
$router->group('',[RateLimitMiddleware::class],function($router):void { $router->add(['GET','POST'],'/mcp',[\App\Controllers\McpController::class,'__invoke']); });
