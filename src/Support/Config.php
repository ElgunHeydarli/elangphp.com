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
    /** Resolve an environment secret or its explicit absolute *_FILE path (for container secret mounts). */
    public static function secret(string $name,string $default=''):string
    {
        $value=self::env($name);if(is_string($value)&&$value!=='') return $value;$path=self::env($name.'_FILE');if($path===null||$path==='') return $default;
        if(!is_string($path)||!str_starts_with($path,'/')||!is_file($path)||!is_readable($path)) throw new \RuntimeException('The configured secret file for '.$name.' is unavailable.');
        $value=file_get_contents($path,false,null,0,8193);if(!is_string($value)||strlen($value)>8192) throw new \RuntimeException('The configured secret file for '.$name.' is invalid.');return rtrim($value,"\r\n");
    }
    public static function env(string $key, mixed $default = null): mixed { return $_ENV[$key] ?? $_SERVER[$key] ?? $default; }
}
