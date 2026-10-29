<?php
declare(strict_types=1);
namespace Elang\Support;
final class Config
{
    public function __construct(private array $values = []) {}
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->values;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) return $default;
            $value = $value[$part];
        }
        return $value;
    }
    public static function env(string $key, mixed $default = null): mixed { return $_ENV[$key] ?? $_SERVER[$key] ?? $default; }
}
