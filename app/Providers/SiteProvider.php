<?php
declare(strict_types=1);
namespace App\Providers;
use Elang\{Application,Container\Container};
use Elang\Contracts\ServiceProvider;
use App\{ContentRepository,Cms\ContentStore,I18n\Locale,I18n\Html,I18n\LocaleMiddleware};
final class SiteProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(Locale::class,fn(Container $c)=>new Locale($c->get(Application::class)));
        $container->singleton(Html::class,fn(Container $c)=>new Html($c->get(Locale::class)));
        $container->singleton(ContentStore::class,fn(Container $c)=>new ContentStore($c->get(\Elang\Database\Database::class),$c->get(\Elang\Validation\Validator::class)));
        $container->singleton(ContentRepository::class,fn(Container $c)=>new ContentRepository($c->get(Application::class)->basePath.'/content',$c->get(ContentStore::class),$c->get(Locale::class)));
        $container->singleton(\Elang\Ai\DocumentSource::class,fn(Container $c)=>new \App\AiDocuments($c->get(ContentRepository::class),$c->get(\Elang\Support\Config::class)));
    }
    public function boot(Application $app): void
    {
        $app->middleware(LocaleMiddleware::class,\App\CanonicalMiddleware::class);
        $app->container->get(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class)->addListener(\Elang\Http\ResponseEvent::class,[$app->container->get(LocaleMiddleware::class),'response']);
        $app->provider(new \Elang\Auth\AuthProvider());
        require_once $app->basePath.'/examples/task-board/app/Controllers/TaskBoardController.php';
        $loader=$app->container->get(\Elang\View\View::class)->engine()->getLoader();
        if($loader instanceof \Twig\Loader\FilesystemLoader) $loader->addPath($app->basePath.'/examples/task-board/resources/views');
    }
}
