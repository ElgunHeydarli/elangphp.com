<?php
declare(strict_types=1);
namespace Elang\Data;
use Closure;
final class Factory
{
    public function __construct(private Repository $repository, private Closure $definition) {}
    public function create(int $count = 1, array $overrides = []): array
    {
        if ($count < 1 || $count > 1000) throw new \InvalidArgumentException('Factory count must be between 1 and 1000.');
        $records = [];
        for ($index = 1; $index <= $count; ++$index) $records[] = $this->repository->create(array_replace(($this->definition)($index), $overrides));
        return $records;
    }
}
