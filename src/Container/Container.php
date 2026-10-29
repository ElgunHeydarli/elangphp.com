<?php
declare(strict_types=1);
namespace Elang\Container;
use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
final class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];
    private array $resolving = [];
    public function bind(string $id, Closure|string $factory, bool $shared = false): void
    {
        $this->bindings[$id] = [$factory, $shared];
        unset($this->instances[$id]);
    }
    public function singleton(string $id, Closure|string $factory): void { $this->bind($id, $factory, true); }
    public function instance(string $id, mixed $instance): void { $this->instances[$id] = $instance; }
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || isset($this->bindings[$id]) || (class_exists($id) && (new ReflectionClass($id))->isInstantiable());
    }
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) return $this->instances[$id];
        if (isset($this->resolving[$id])) throw new ContainerException('Circular dependency: '.implode(' -> ', array_keys($this->resolving)).' -> '.$id);
        if (!$this->has($id)) throw new NotFoundException('Service not found: '.$id);
        $this->resolving[$id] = true;
        try {
            [$factory, $shared] = $this->bindings[$id] ?? [$id, false];
            if ($factory instanceof Closure) $value = $factory($this);
            else {
                $class = new ReflectionClass($factory);
                if (!$class->isInstantiable()) throw new ContainerException('Service is not instantiable: '.$factory);
                $constructor = $class->getConstructor();
                $value = $class->newInstanceArgs($constructor ? $this->arguments($constructor->getParameters(), []) : []);
            }
            if ($shared) $this->instances[$id] = $value;
            return $value;
        } finally { unset($this->resolving[$id]); }
    }
    public function invoke(callable|array|string $handler, array $parameters = []): mixed
    {
        if (is_array($handler)) {
            $target = is_string($handler[0]) ? $this->get($handler[0]) : $handler[0];
            $reflection = new ReflectionMethod($target, $handler[1]);
            return $reflection->invokeArgs($target, $this->arguments($reflection->getParameters(), $parameters));
        }
        if (is_string($handler) && class_exists($handler)) return $this->invoke([$handler, '__invoke'], $parameters);
        $closure = Closure::fromCallable($handler);
        $reflection = new ReflectionFunction($closure);
        return $closure(...$this->arguments($reflection->getParameters(), $parameters));
    }
    /** @param ReflectionParameter[] $parameters */
    private function arguments(array $parameters, array $supplied): array
    {
        $arguments = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            if (array_key_exists($name, $supplied)) {
                $value = $supplied[$name];
                if ($type instanceof ReflectionNamedType && $type->getName() === 'int' && is_string($value)) {
                    $value = filter_var($value, FILTER_VALIDATE_INT);
                    if ($value === false) throw new \Elang\Http\HttpException(400, 'Invalid integer route parameter.');
                }
                $arguments[] = $value;
            } elseif ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $this->has($type->getName())) $arguments[] = $this->get($type->getName());
            elseif ($parameter->isDefaultValueAvailable()) $arguments[] = $parameter->getDefaultValue();
            elseif ($parameter->allowsNull()) $arguments[] = null;
            else throw new ContainerException('Cannot resolve argument $'.$name.'. Bind its service or provide a value.');
        }
        return $arguments;
    }
}
