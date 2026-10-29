<?php
declare(strict_types=1);
namespace Elang\Data;
abstract class Model implements \JsonSerializable
{
    protected const TABLE = '';
    protected const FILLABLE = [];
    protected const HIDDEN = [];
    protected const CASTS = [];
    public function __construct(private readonly array $attributes = []) {}
    public static function table(): string { return static::TABLE; }
    public static function fillable(): array { return static::FILLABLE; }
    public function get(string $name, mixed $default = null): mixed
    {
        $value = $this->attributes[$name] ?? $default;
        if ($value === null) return null;
        return match (static::CASTS[$name] ?? null) { 'int' => (int) $value, 'bool' => (bool) $value, 'float' => (float) $value, 'json' => is_string($value) ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : $value, default => $value };
    }
    public function __get(string $name): mixed { return $this->get($name); }
    public function __isset(string $name): bool { return isset($this->attributes[$name]); }
    public function toArray(): array
    {
        $data = [];
        foreach ($this->attributes as $name => $value) if (!in_array($name, static::HIDDEN, true)) $data[$name] = $this->get($name);
        return $data;
    }
    public function jsonSerialize(): array { return $this->toArray(); }
}
