<?php
declare(strict_types=1);
namespace Elang\Routing;
final class Route
{
    public function __construct(public readonly string $name, private \Symfony\Component\Routing\Route $route) {}
    public function where(string $parameter, string $pattern): self { $this->route->setRequirement($parameter, $pattern); return $this; }
    public function middleware(string ...$classes): self { $this->route->setDefault('_middleware', array_merge($this->route->getDefault('_middleware') ?? [], $classes)); return $this; }
}
