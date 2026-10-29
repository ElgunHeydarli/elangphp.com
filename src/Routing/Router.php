<?php
declare(strict_types=1);
namespace Elang\Routing;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\{RouteCollection, RequestContext};
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
final class Router
{
    private RouteCollection $routes;
    private array $groups = [];
    public function __construct() { $this->routes = new RouteCollection(); }
    public function get(string $path, callable|array|string $handler, ?string $name = null): Route { return $this->add(['GET', 'HEAD'], $path, $handler, $name); }
    public function post(string $path, callable|array|string $handler, ?string $name = null): Route { return $this->add(['POST'], $path, $handler, $name); }
    public function put(string $path, callable|array|string $handler, ?string $name = null): Route { return $this->add(['PUT'], $path, $handler, $name); }
    public function patch(string $path, callable|array|string $handler, ?string $name = null): Route { return $this->add(['PATCH'], $path, $handler, $name); }
    public function delete(string $path, callable|array|string $handler, ?string $name = null): Route { return $this->add(['DELETE'], $path, $handler, $name); }
    public function add(array $methods, string $path, callable|array|string $handler, ?string $name = null): Route
    {
        $prefix = implode('', array_column($this->groups, 'prefix'));
        $middleware = [];
        foreach ($this->groups as $group) $middleware = array_merge($middleware, $group['middleware']);
        $route = new \Symfony\Component\Routing\Route($prefix.$path, ['_handler' => $handler, '_middleware' => $middleware], [], [], '', [], $methods);
        $name ??= 'route_'.count($this->routes);
        if ($this->routes->get($name)) throw new \InvalidArgumentException('Duplicate route name: '.$name);
        $this->routes->add($name, $route);
        return new Route($name, $route);
    }
    public function group(string $prefix, array $middleware, callable $routes): void
    {
        $this->groups[] = ['prefix' => rtrim($prefix, '/'), 'middleware' => $middleware];
        try { $routes($this); } finally { array_pop($this->groups); }
    }
    public function match(Request $request): array { return (new UrlMatcher($this->routes, (new RequestContext())->fromRequest($request)))->match($request->attributes->get('_route_path',$request->getPathInfo())); }
    public function url(string $name, array $parameters = []): string { return (new UrlGenerator($this->routes, new RequestContext()))->generate($name, $parameters); }
    public function all(): RouteCollection { return $this->routes; }
}
