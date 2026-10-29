<?php
declare(strict_types=1);
namespace App\Providers;
use Elang\Application;
use Elang\Container\Container;
use Elang\Contracts\ServiceProvider;
use App\ContentRepository;
final class SiteProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(ContentRepository::class, fn (Container $c) => new ContentRepository($c->get(Application::class)->basePath.'/content'));
    }
    public function boot(Application $app): void
    {
        $app->middleware(\App\CanonicalMiddleware::class);
        require_once $app->basePath.'/examples/task-board/app/Controllers/TaskBoardController.php';
        $loader = $app->container->get(\Elang\View\View::class)->engine()->getLoader();
        if ($loader instanceof \Twig\Loader\FilesystemLoader) $loader->addPath($app->basePath.'/examples/task-board/resources/views');
    }
}
