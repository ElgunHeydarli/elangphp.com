<?php
declare(strict_types=1);
namespace Elang\Support;

/** The factory must return a fresh iterable for each traversal. */
final class LazyCollection implements \IteratorAggregate
{
    private \Closure $factory;
    public function __construct(callable $factory) { $this->factory = \Closure::fromCallable($factory); }
    public function getIterator(): \Traversable { yield from ($this->factory)(); }
    public function map(callable $callback): self
    {
        return new self(function () use ($callback) { foreach ($this as $key => $value) yield $key => $callback($value, $key); });
    }
    public function filter(callable $callback): self
    {
        return new self(function () use ($callback) { foreach ($this as $key => $value) if ($callback($value, $key)) yield $key => $value; });
    }
    public function take(int $count): self
    {
        if ($count < 0) throw new \InvalidArgumentException('Take count must be non-negative.');
        return new self(function () use ($count) { if ($count === 0) return; $seen = 0; foreach ($this as $key => $value) { yield $key => $value; if (++$seen === $count) return; } });
    }
    public function chunk(int $size): self
    {
        if ($size < 1) throw new \InvalidArgumentException('Chunk size must be positive.');
        return new self(function () use ($size) { $chunk = []; foreach ($this as $item) { $chunk[] = $item; if (count($chunk) === $size) { yield new Collection($chunk); $chunk = []; } } if ($chunk) yield new Collection($chunk); });
    }
    public function each(callable $callback): void { foreach ($this as $key => $value) if ($callback($value, $key) === false) return; }
    public function collect(): Collection { return new Collection($this); }
}
