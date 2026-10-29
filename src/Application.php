<?php
declare(strict_types=1);
namespace Elang;
use Closure;
use Dotenv\Dotenv;
use Elang\Container\Container;
use Elang\Contracts\{Middleware, ServiceProvider};
use Elang\Database\{Database, Migrator};
use Elang\Http\{HttpException, Response};
use Elang\Http\Middleware\SecurityHeaders;
use Elang\Routing\Router;
use Elang\Security\Encryption;
use Elang\Support\Config;
use Elang\Validation\ValidationException;
use Elang\View\View;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\{Request, Response as HttpResponse};
use Symfony\Component\Mailer\{Mailer, MailerInterface, Transport};
use Symfony\Component\RateLimiter\{RateLimiterFactory, Storage\CacheStorage};
use Symfony\Component\Lock\{LockFactory, Store\FlockStore};
use Symfony\Component\Routing\Exception\{ResourceNotFoundException, MethodNotAllowedException};
use Twig\{Environment, TwigFunction};
use Twig\Loader\FilesystemLoader;
final class Application
{
    public const VERSION = '0.1.0-dev';
    public readonly Container $container;
    public readonly Router $router;
    public readonly Config $config;
    private array $middleware = [];
    public function __construct(public readonly string $basePath, ?array $settings = null)
    {
        Dotenv::createImmutable($basePath)->safeLoad();
        $values = $settings ?? (is_file($basePath.'/config/app.php') ? require $basePath.'/config/app.php' : []);
        $values = array_replace_recursive([
            'environment' => Config::env('APP_ENV', 'production'),
            'debug' => filter_var(Config::env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
            'url' => Config::env('APP_URL', 'http://localhost:8000'),
            'key' => Config::env('APP_KEY', ''),
            'database' => ['url' => Config::env('DATABASE_URL', 'sqlite:///'. $basePath.'/database/database.sqlite')],
            'mail' => ['dsn' => Config::env('MAILER_DSN', 'null://null'), 'from' => Config::env('MAIL_FROM','noreply@localhost')],
        ], $values);
        $storage = $values['paths']['storage'] ?? $basePath.'/storage';
        $values['paths'] = array_replace(['base' => $basePath, 'storage' => $storage, 'views' => $basePath.'/resources/views'], $values['paths'] ?? []);
        foreach (['cache', 'logs', 'sessions', 'locks'] as $directory) {
            $path = $storage.'/'.$directory;
            if (!is_dir($path) && !mkdir($path, 0770, true) && !is_dir($path)) throw new \RuntimeException('Cannot create storage directory.');
        }
        $this->config = new Config($values);
        $this->container = $c = new Container();
        $this->router = new Router();
        foreach ([self::class => $this, Container::class => $c, ContainerInterface::class => $c, Config::class => $this->config, Router::class => $this->router] as $id => $value) $c->instance($id, $value);
        $c->singleton(\Elang\Queue\Jobs::class,fn()=>new \Elang\Queue\Jobs($c));
        $c->singleton(\Elang\Queue\Schedule::class,fn()=>new \Elang\Queue\Schedule($c->get(Database::class),$c->get(\Elang\Queue\Queue::class)));
        $c->singleton(\Elang\Realtime\Messages::class,fn()=>new \Elang\Realtime\Messages($c));
        $c->singleton(\Elang\Auth\Gate::class,fn()=>new \Elang\Auth\Gate());
        $c->singleton(LoggerInterface::class, function () use ($storage) {
            $logger = new Logger('elang');
            $logger->pushHandler(new StreamHandler($storage.'/logs/app.log', Logger::WARNING));
            return $logger;
        });
        $c->singleton(FilesystemAdapter::class, fn () => new FilesystemAdapter('elang', 0, $storage.'/cache'));
        $c->singleton(CacheInterface::class, fn (Container $c) => new Psr16Cache($c->get(FilesystemAdapter::class)));
        $c->singleton(RateLimiterFactory::class, fn (Container $c) => new RateLimiterFactory(
            ['id' => 'public-api', 'policy' => 'fixed_window', 'limit' => $this->config->get('rate_limit', 120), 'interval' => '1 minute'],
            new CacheStorage($c->get(FilesystemAdapter::class)), new LockFactory(new FlockStore($storage.'/locks'))
        ));
        $c->singleton(\Elang\Debug\Collector::class,fn()=>new \Elang\Debug\Collector($this->config));
        $c->singleton(Database::class, fn (Container $c) => new Database($this->config->get('database.url', 'sqlite:///'.$basePath.'/database/database.sqlite'),$c->get(\Elang\Debug\Collector::class)));
        $c->singleton(Migrator::class, fn (Container $c) => new Migrator($c->get(Database::class), $basePath.'/database/migrations', $storage.'/locks/migrations.lock'));
        $c->singleton(Encryption::class, fn () => new Encryption($this->config->get('key', '')));
        $c->singleton(EventDispatcherInterface::class, fn () => new EventDispatcher());
        $c->singleton(MailerInterface::class, fn () => new Mailer(Transport::fromDsn($this->config->get('mail.dsn', 'null://null'))));
        $this->router->get('/_elang/assets/ui.css', fn () => new HttpResponse(file_get_contents(dirname(__DIR__).'/resources/elang/ui.css'),200,['Content-Type'=>'text/css; charset=utf-8','Cache-Control'=>'public, max-age=3600']),'elang.ui');
        if ($this->config->get('environment')==='local') {
            $this->router->get('/_elang/studio',[\Elang\Debug\StudioController::class,'index'],'elang.studio');
            $this->router->get('/_elang/studio/routes',[\Elang\Debug\StudioController::class,'routes']);
            $this->router->get('/_elang/studio/mail',[\Elang\Debug\StudioController::class,'mail']);
            $this->router->get('/_elang/studio/requests/{id}',[\Elang\Debug\StudioController::class,'trace'])->where('id','[a-f0-9]{16}');
        }
        $c->singleton(View::class, function () use ($storage) {
            $loader = new FilesystemLoader($this->config->get('paths.views'));
            $loader->addPath(dirname(__DIR__).'/resources/elang','elang');
            $twig = new Environment($loader, ['cache' => $storage.'/cache/views', 'autoescape' => 'html', 'strict_variables' => true, 'auto_reload' => true]);
            $twig->addGlobal('app_name',$this->config->get('name','Elang'));
            $twig->addGlobal('app_starter',$this->config->get('starter',''));
            $twig->addGlobal('version', self::VERSION);
            $twig->addGlobal('elang_asset_version',filemtime(dirname(__DIR__).'/resources/elang/ui.css'));
            $twig->addGlobal('site_url', rtrim($this->config->get('url', 'http://localhost:8000'), '/'));
            $twig->addFunction(new TwigFunction('route', fn (string $name, array $parameters = []) => $this->router->url($name, $parameters)));
            return new View($twig);
        });
    }
    public static function create(string $basePath): self { return new self($basePath); }
    public function provider(ServiceProvider $provider): self { $provider->register($this->container); $provider->boot($this); return $this; }
    public function middleware(string ...$classes): self { $this->middleware = array_merge($this->middleware, $classes); return $this; }
    public function handle(Request $request): HttpResponse
    {
        $id = bin2hex(random_bytes(8));
        $collector = $this->container->get(\Elang\Debug\Collector::class);
        $collector->start();
        $this->container->instance(Request::class, $request);
        try {
            if (strlen($request->getContent()) > $this->config->get('max_body_bytes', 1048576)) throw new HttpException(413, 'Request body is too large.');
            $response = $this->pipeline($request, $this->middleware, function (Request $request): HttpResponse {
                $match = $this->router->match($request);
                $request->attributes->add($match);
                $parameters = array_filter($match, fn ($key) => !str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY);
                return $this->pipeline($request, $match['_middleware'], function () use ($match, $parameters): HttpResponse {
                    $result = $this->container->invoke($match['_handler'], $parameters);
                    return match (true) {
                        $result instanceof HttpResponse => $result,
                        is_array($result) => Response::json($result),
                        is_string($result) => Response::html($result),
                        $result === null => new HttpResponse('', 204),
                        default => throw new \LogicException('Handlers must return a response, array, string, or null.'),
                    };
                });
            });
        } catch (ResourceNotFoundException) { $response = $this->error($request, 404, 'The page you requested could not be found.', $id); }
        catch (MethodNotAllowedException $e) { $response = $this->error($request, 405, 'This HTTP method is not allowed.', $id, ['Allow' => implode(', ', $e->getAllowedMethods())]); }
        catch (\Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface) { $response = $this->error($request, 400, 'The request contains invalid data.', $id); }
        catch (ValidationException $e) { $response = Response::json(['message' => $e->getMessage(), 'errors' => $e->errors, 'request_id' => $id], 422); }
        catch (HttpException $e) { $response = $this->error($request, $e->status, $e->getMessage(), $id, $e->headers); }
        catch (\Throwable $e) {
            $collector->error($e);
            try { $this->container->get(LoggerInterface::class)->error('Unhandled exception', ['exception' => $e, 'request_id' => $id]); } catch (\Throwable) {}
            $message = $this->config->get('debug', false) ? $e->getMessage() : 'Something went wrong. Please try again later.';
            $response = $this->error($request, 500, $message, $id);
        }
        \Elang\Http\Middleware\Cors::apply($request,$response);
        $this->container->get(EventDispatcherInterface::class)->dispatch(new \Elang\Http\ResponseEvent($request,$response));
        SecurityHeaders::apply($response);
        $response->headers->set('X-Request-Id', $id);
        $collector->finish($request,$response,$id);
        return $response->prepare($request);
    }
    private function pipeline(Request $request, array $middleware, Closure $destination): HttpResponse
    {
        $next = array_reduce(array_reverse($middleware), function (Closure $next, string $class): Closure {
            return function (Request $request) use ($class, $next): HttpResponse {
                $middleware = $this->container->get($class);
                if (!$middleware instanceof Middleware) throw new \LogicException('Middleware must implement '.Middleware::class);
                return $middleware->process($request, $next);
            };
        }, $destination);
        return $next($request);
    }
    private function error(Request $request, int $status, string $message, string $id, array $headers = []): HttpResponse
    {
        if (str_starts_with($request->getPathInfo(), '/api/') || str_contains($request->headers->get('Accept', ''), 'application/json')) return Response::json(['message' => $message, 'request_id' => $id], $status, $headers);
        $safe = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return new HttpResponse('<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$status.' — Elang</title><link rel="stylesheet" href="/assets/app.css"><body class="error-page"><a class="wordmark" href="/">elang<span>php</span></a><main><p class="eyebrow">HTTP '.$status.'</p><h1>Let’s find your way back.</h1><p>'.$safe.'</p><a class="button primary" href="/docs/v0.1/introduction">Open documentation →</a><p class="muted">Request '.$id.'</p></main></body></html>', $status, array_merge(['Content-Type' => 'text/html; charset=utf-8'], $headers));
    }
    public function run(): void { $this->handle(Request::createFromGlobals())->send(); }
}
