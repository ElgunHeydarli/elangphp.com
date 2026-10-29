<?php
declare(strict_types=1);
namespace Elang\Data;
final readonly class Paginator implements \JsonSerializable, \IteratorAggregate, \Countable
{
    public int $lastPage;
    public function __construct(public array $items, public int $total, public int $perPage, public int $currentPage)
    {
        $this->lastPage = max(1, (int) ceil($total / $perPage));
    }
    public function count(): int { return count($this->items); }
    public function getIterator(): \Traversable { return new \ArrayIterator($this->items); }
    public function jsonSerialize(): array { return ['data' => $this->items, 'meta' => ['total' => $this->total, 'per_page' => $this->perPage, 'current_page' => $this->currentPage, 'last_page' => $this->lastPage]]; }
}
