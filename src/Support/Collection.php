<?php
declare(strict_types=1);
namespace Elang\Support;

/** Immutable eager transformations. Use LazyCollection for a bounded-memory stream. */
final class Collection implements \IteratorAggregate, \Countable, \JsonSerializable
{
    private array $items;
    public function __construct(iterable $items = []) { $this->items = is_array($items) ? $items : iterator_to_array($items); }
    public static function make(iterable $items = []): self { return new self($items); }
    public function all(): array { return $this->items; }
    public function count(): int { return count($this->items); }
    public function isEmpty(): bool { return $this->items === []; }
    public function getIterator(): \Traversable { return new \ArrayIterator($this->items); }
    public function get(int|string $key, mixed $default = null): mixed { return array_key_exists($key, $this->items) ? $this->items[$key] : $default; }
    public function keys(): self { return new self(array_keys($this->items)); }
    public function values(): self { return new self(array_values($this->items)); }
    public function map(callable $callback): self
    {
        $out = []; foreach ($this->items as $key => $value) $out[$key] = $callback($value, $key); return new self($out);
    }
    public function filter(?callable $callback = null): self
    {
        $out = []; foreach ($this->items as $key => $value) if ($callback ? $callback($value, $key) : (bool) $value) $out[$key] = $value; return new self($out);
    }
    public function reject(callable $callback): self { return $this->filter(fn ($value, $key) => !$callback($value, $key)); }
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $value) if ($callback($value, $key) === false) break; return $this;
    }
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        foreach ($this->items as $key => $value) if (!$callback || $callback($value, $key)) return $value; return $default;
    }
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        foreach ($this->items as $key => $value) $initial = $callback($initial, $value, $key); return $initial;
    }
    public function pluck(string $path, ?string $keyPath = null): self
    {
        $out = [];
        foreach ($this->items as $item) {
            $value = Arr::get($item, $path);
            if ($keyPath === null) $out[] = $value;
            else { $key = Arr::get($item, $keyPath); if (!is_int($key) && !is_string($key)) throw new \InvalidArgumentException('Collection keys must be strings or integers.'); $out[$key] = $value; }
        }
        return new self($out);
    }
    public function keyBy(string|callable $selector): self
    {
        $out = [];
        foreach ($this->items as $key => $item) {
            $index = is_string($selector) ? Arr::get($item, $selector) : $selector($item, $key);
            if (!is_int($index) && !is_string($index)) throw new \InvalidArgumentException('Collection keys must be strings or integers.');
            $out[$index] = $item;
        }
        return new self($out);
    }
    public function groupBy(string|callable $selector): self
    {
        $out = [];
        foreach ($this->items as $key => $item) {
            $index = is_string($selector) ? Arr::get($item, $selector) : $selector($item, $key);
            if (!is_int($index) && !is_string($index)) throw new \InvalidArgumentException('Group keys must be strings or integers.');
            $out[$index][] = $item;
        }
        return new self(array_map(fn ($items) => new self($items), $out));
    }
    public function where(string $path, mixed $expected): self { return $this->filter(fn ($item) => Arr::get($item, $path) === $expected); }
    public function sortBy(string|callable $selector, bool $descending = false): self
    {
        $out = $this->items; $value = fn ($item) => is_string($selector) ? Arr::get($item, $selector) : $selector($item);
        uasort($out, fn ($a, $b) => ($value($a) <=> $value($b)) * ($descending ? -1 : 1)); return new self($out);
    }
    public function take(int $count): self
    {
        if ($count < 0) throw new \InvalidArgumentException('Take count must be non-negative.'); return new self(array_slice($this->items, 0, $count, true));
    }
    public function chunk(int $size): self
    {
        if ($size < 1) throw new \InvalidArgumentException('Chunk size must be positive.'); return new self(array_map(fn ($chunk) => new self($chunk), array_chunk($this->items, $size, true)));
    }
    public function sum(?string $path = null): int|float
    {
        return $this->reduce(function ($sum, $value) use ($path) { $number = $path === null ? $value : Arr::get($value, $path); if (!is_int($number) && !is_float($number)) throw new \InvalidArgumentException('Sum requires numeric values.'); return $sum + $number; }, 0);
    }
    public function toArray(): array
    {
        $normalize = function ($value) use (&$normalize) { if ($value instanceof \JsonSerializable) $value = $value->jsonSerialize(); return is_array($value) ? array_map($normalize, $value) : $value; };
        return array_map($normalize, $this->items);
    }
    public function jsonSerialize(): array { return $this->toArray(); }
}
