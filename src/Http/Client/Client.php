<?php
declare(strict_types=1);
namespace Elang\Http\Client;
final class Client
{
    private Transport $transport;
    public function __construct(?Transport $transport = null) { $this->transport = $transport ?? new GuzzleTransport(); }
    public function request(): PendingRequest { return new PendingRequest($this->transport); }
    public function baseUrl(string $url): PendingRequest { return $this->request()->baseUrl($url); }
    public function withHeaders(array $headers): PendingRequest { return $this->request()->withHeaders($headers); }
    public function withToken(#[\SensitiveParameter] string $token, string $scheme = 'Bearer'): PendingRequest { return $this->request()->withToken($token,$scheme); }
    public function timeout(float $seconds): PendingRequest { return $this->request()->timeout($seconds); }
    public function retry(int $attempts,int $delayMilliseconds = 0,bool $retryUnsafe = false): PendingRequest { return $this->request()->retry($attempts,$delayMilliseconds,$retryUnsafe); }
    public function get(string $url,array $query = []): Response { return $this->request()->get($url,$query); }
    public function post(string $url, #[\SensitiveParameter] array $data = []): Response { return $this->request()->post($url,$data); }
}
