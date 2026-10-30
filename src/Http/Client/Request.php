<?php
declare(strict_types=1);
namespace Elang\Http\Client;
final readonly class Request
{
    public function __construct(public string $method, public string $url, public array $headers, public string $body, public float $timeout = 10, public float $connectTimeout = 5, public int $maxResponseBytes = 8388608) {}
    public function header(string $name): ?string { return $this->headers[strtolower($name)] ?? null; }
    public function __debugInfo(): array { $url = parse_url($this->url); return ['method'=>$this->method,'origin'=>($url['scheme']??'').'://'.($url['host']??''),'headers'=>array_keys($this->headers),'body'=>'[redacted]']; }
}
