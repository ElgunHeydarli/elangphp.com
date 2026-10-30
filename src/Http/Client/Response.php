<?php
declare(strict_types=1);
namespace Elang\Http\Client;
use Elang\Support\Arr;
final readonly class Response
{
    public function __construct(public int $status, public string $body = '', public array $headers = [])
    {
        if ($status < 100 || $status > 599) throw new \InvalidArgumentException('Invalid HTTP response status.');
    }
    public function successful(): bool { return $this->status >= 200 && $this->status < 300; }
    public function failed(): bool { return $this->status >= 400; }
    public function redirect(): bool { return $this->status >= 300 && $this->status < 400; }
    public function header(string $name): ?string
    {
        foreach ($this->headers as $key=>$value) if (strtolower($key)===strtolower($name)) return is_array($value) ? implode(', ', $value) : (string) $value; return null;
    }
    public function json(?string $path = null, mixed $default = null): mixed
    {
        $data = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR); return $path === null ? $data : Arr::get($data, $path, $default);
    }
    public function throw(): self { if ($this->failed()) throw new RequestException($this); return $this; }
    public function __debugInfo(): array { return ['status'=>$this->status,'headers'=>array_keys($this->headers),'body'=>'[redacted]']; }
}
