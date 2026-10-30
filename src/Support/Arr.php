<?php
declare(strict_types=1);
namespace Elang\Support;

/** Small, explicit dot-path helpers for application data, not SQL expressions. */
final class Arr
{
    public static function get(mixed $data, string $path, mixed $default = null): mixed
    {
        if ($path === '') return $data;
        if (is_array($data) && array_key_exists($path, $data)) return $data[$path];
        foreach (explode('.', $path) as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) $data = $data[$segment];
            elseif ($data instanceof \Elang\Data\Model) $data = $data->get($segment, $default);
            elseif (is_object($data) && (isset($data->{$segment}) || array_key_exists($segment, get_object_vars($data)))) $data = $data->{$segment};
            else return $default;
        }
        return $data;
    }

    public static function has(mixed $data, string $path): bool
    {
        $missing = new \stdClass();
        return self::get($data, $path, $missing) !== $missing;
    }

    public static function only(array $data, array $keys): array { return array_intersect_key($data, array_fill_keys($keys, true)); }
    public static function except(array $data, array $keys): array { return array_diff_key($data, array_fill_keys($keys, true)); }
}
