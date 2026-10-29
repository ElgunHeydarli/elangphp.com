<?php
declare(strict_types=1);
namespace Elang\Data;
use Elang\Database\Database;
final class Models
{
    public function __construct(private Database $database) {}
    /** @param class-string<Model> $class */
    public function of(string $class): Repository { return new Repository($this->database, $class); }
    /** @param class-string<Model> $related */
    public function hasMany(Model $parent, string $related, string $foreignKey, string $localKey = 'id'): Repository
    {
        return $this->of($related)->where($foreignKey, $parent->get($localKey));
    }
    /** @param class-string<Model> $related */
    public function belongsTo(Model $child, string $related, string $foreignKey): ?Model
    {
        $id = $child->get($foreignKey); return $id === null ? null : $this->of($related)->find($id);
    }
}
