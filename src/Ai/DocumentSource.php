<?php
declare(strict_types=1);
namespace Elang\Ai;
interface DocumentSource
{
    public function search(string $query,int $limit=10): array;
    public function read(string $path): ?array;
    public function list(): array;
}
