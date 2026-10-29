<?php
declare(strict_types=1);
namespace Elang\Data;
use Elang\Database\Database;
use Doctrine\DBAL\Query\QueryBuilder;
final class Repository
{
    private array $conditions = [];
    private array $orders = [];
    /** @param class-string<Model> $class */
    public function __construct(private Database $database, private string $class)
    {
        $this->validateClass($class);
        $this->identifier($class::table());
    }
    private function validateClass(string $class):void { if(!is_subclass_of($class,Model::class)) throw new \InvalidArgumentException('Repositories require an Elang Model class.'); }
    private function identifier(string $value): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $value)) throw new \InvalidArgumentException('Use a simple, application-controlled SQL identifier.');
        return $this->database->connection()->quoteIdentifier($value);
    }
    public function where(string $column, mixed $value, string $operator = '='): self
    {
        if (!in_array($operator, ['=', '!=', '<', '<=', '>', '>=', 'LIKE'], true)) throw new \InvalidArgumentException('Unsupported comparison operator.');
        $this->identifier($column); $copy = clone $this; $copy->conditions[] = [$column, $operator, $value]; return $copy;
    }
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->identifier($column); $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC','DESC'], true)) throw new \InvalidArgumentException('Order direction must be ASC or DESC.');
        $copy = clone $this; $copy->orders[] = [$column, $direction]; return $copy;
    }
    private function query(bool $count = false): QueryBuilder
    {
        $query = $this->database->connection()->createQueryBuilder()->select($count ? 'COUNT(*)' : '*')->from($this->identifier($this->class::table()));
        foreach ($this->conditions as $index => [$column, $operator, $value]) {
            $name = 'filter_'.$index;
            if ($value === null && in_array($operator, ['=', '!='], true)) $query->andWhere($this->identifier($column).($operator === '=' ? ' IS NULL' : ' IS NOT NULL'));
            else { $query->andWhere($this->identifier($column).' '.$operator.' :'.$name)->setParameter($name, $value); }
        }
        if (!$count) foreach ($this->orders as [$column, $direction]) $query->addOrderBy($this->identifier($column), $direction);
        return $query;
    }
    public function get(int $limit = 100): array
    {
        if ($limit < 1 || $limit > 1000) throw new \InvalidArgumentException('Result limit must be between 1 and 1000.');
        return array_map(fn ($row) => new $this->class($row), $this->query()->setMaxResults($limit)->fetchAllAssociative());
    }
    public function first(): ?Model { $row = $this->query()->setMaxResults(1)->fetchAssociative(); return $row === false ? null : new $this->class($row); }
    public function find(int|string $id): ?Model { return $this->where('id', $id)->first(); }
    public function count(): int { return (int) $this->query(true)->fetchOne(); }
    public function paginate(int $perPage = 15, int $page = 1): Paginator
    {
        if ($perPage < 1 || $perPage > 100 || $page < 1 || $page > 1000000) throw new \InvalidArgumentException('Invalid pagination bounds.');
        $rows = $this->query()->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage)->fetchAllAssociative();
        return new Paginator(array_map(fn ($row) => new $this->class($row), $rows), $this->count(), $perPage, $page);
    }
    private function writable(array $data): array
    {
        if (!$data) throw new \InvalidArgumentException('Supply at least one writable field.');
        foreach ($data as $column => $value) {
            if (!in_array($column, $this->class::fillable(), true)) throw new \InvalidArgumentException('Field is not fillable: '.$column);
            $this->identifier($column);
            if (is_array($value) || is_object($value)) $data[$column] = json_encode($value, JSON_THROW_ON_ERROR);
        }
        return $data;
    }
    public function create(array $data): Model
    {
        foreach ($this->conditions as [$column,$operator,$value]) if ($operator !== '=' || !array_key_exists($column,$data) || $data[$column] != $value) throw new \InvalidArgumentException('Created data must match every repository scope.');
        $data = $this->writable($data); $connection = $this->database->connection();
        $connection->insert($this->identifier($this->class::table()), array_combine(array_map(fn ($name) => $this->identifier($name), array_keys($data)), array_values($data)));
        $id = $connection->lastInsertId();
        $model = $this->find($id);
        if (!$model) throw new \RuntimeException('Created record could not be read with the current repository scope.');
        return $model;
    }
    public function update(int|string $id, array $data): ?Model
    {
        if (!$this->find($id)) return null;
        $data = $this->writable($data); $query = $this->query()->update($this->identifier($this->class::table()));
        foreach ($data as $column => $value) $query->set($this->identifier($column), ':write_'.$column)->setParameter('write_'.$column, $value);
        $query->andWhere($this->identifier('id').' = :record_id')->setParameter('record_id', $id)->executeStatement();
        return $this->find($id);
    }
    public function delete(int|string $id): bool
    {
        return $this->query()->delete($this->identifier($this->class::table()))->andWhere($this->identifier('id').' = :record_id')->setParameter('record_id', $id)->executeStatement() === 1;
    }
}
