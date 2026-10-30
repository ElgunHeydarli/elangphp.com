<?php
declare(strict_types=1);
namespace Elang\Http\Client;
final class PendingRequest
{
    private string $base = '';
    private array $headers = ['accept'=>'application/json'];
    private float $timeout = 10;
    private float $connectTimeout = 5;
    private int $maxBytes = 8388608;
    private int $attempts = 1;
    private int $delay = 0;
    private bool $retryUnsafe = false;
    private bool $form = false;
    public function __construct(private Transport $transport) {}
    private static function validateUrl(string $url): void
    {
        $parts = parse_url($url);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !is_array($parts) || !in_array(strtolower($parts['scheme']??''), ['http','https'], true) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment']) || preg_match('/[\x00-\x20\x7f]/', $url)) throw new \InvalidArgumentException('Use an absolute HTTP(S) URL without credentials, fragments or control characters.');
    }
    private static function origin(string $url): string
    {
        $parts = parse_url($url); return strtolower($parts['scheme'].'://'.$parts['host']).':'.($parts['port']??(strtolower($parts['scheme'])==='https'?443:80));
    }
    public function baseUrl(string $url): self
    {
        self::validateUrl($url); if (isset(parse_url($url)['query'])) throw new \InvalidArgumentException('A base URL must not contain a query string.'); $copy = clone $this; $copy->base = rtrim($url, '/'); return $copy;
    }
    public function withHeaders(array $headers): self
    {
        $copy = clone $this;
        foreach ($headers as $name=>$value) {
            if (!is_string($name) || !preg_match('/^[!#$%&\x27*+.^_`|~0-9A-Za-z-]+$/D', $name) || !is_string($value) || preg_match('/[\r\n\x00]/', $value)) throw new \InvalidArgumentException('Invalid HTTP header.');
            $copy->headers[strtolower($name)] = $value;
        }
        return $copy;
    }
    public function withToken(#[\SensitiveParameter] string $token, string $scheme = 'Bearer'): self
    {
        if ($token === '' || !preg_match('/^[A-Za-z][A-Za-z0-9._~-]*$/D', $scheme)) throw new \InvalidArgumentException('Invalid authorization configuration.'); return $this->withHeaders(['Authorization'=>$scheme.' '.$token]);
    }
    public function timeout(float $seconds): self
    {
        if (!is_finite($seconds) || $seconds<=0 || $seconds>300) throw new \InvalidArgumentException('Timeout must be between zero and 300 seconds.'); $copy=clone $this;$copy->timeout=$seconds;return $copy;
    }
    public function connectTimeout(float $seconds): self
    {
        if (!is_finite($seconds) || $seconds<=0 || $seconds>300) throw new \InvalidArgumentException('Connect timeout must be between zero and 300 seconds.'); $copy=clone $this;$copy->connectTimeout=$seconds;return $copy;
    }
    public function maxResponseBytes(int $bytes): self
    {
        if ($bytes<1 || $bytes>67108864) throw new \InvalidArgumentException('Response limit must be between 1 byte and 64 MiB.'); $copy=clone $this;$copy->maxBytes=$bytes;return $copy;
    }
    public function retry(int $attempts, int $delayMilliseconds = 0, bool $retryUnsafe = false): self
    {
        if ($attempts<1 || $attempts>5 || $delayMilliseconds<0 || $delayMilliseconds>10000) throw new \InvalidArgumentException('Use 1–5 attempts and a delay of at most ten seconds.'); $copy=clone $this;$copy->attempts=$attempts;$copy->delay=$delayMilliseconds;$copy->retryUnsafe=$retryUnsafe;return $copy;
    }
    public function asForm(): self { $copy=clone $this;$copy->form=true;return $copy; }
    public function get(string $url, array $query = []): Response
    {
        if ($query) $url .= (str_contains($url,'?')?'&':'?').http_build_query($query,'','&',PHP_QUERY_RFC3986); return $this->send('GET',$url);
    }
    public function post(string $url, #[\SensitiveParameter] array $data = []): Response { return $this->data('POST',$url,$data); }
    public function put(string $url, #[\SensitiveParameter] array $data = []): Response { return $this->data('PUT',$url,$data); }
    public function patch(string $url, #[\SensitiveParameter] array $data = []): Response { return $this->data('PATCH',$url,$data); }
    public function delete(string $url, #[\SensitiveParameter] array $data = []): Response { return $this->data('DELETE',$url,$data); }
    private function data(string $method,string $url,array $data): Response
    {
        return $this->withHeaders(['Content-Type'=>$this->form?'application/x-www-form-urlencoded':'application/json'])->send($method,$url,$this->form?http_build_query($data,'','&',PHP_QUERY_RFC3986):json_encode($data,JSON_THROW_ON_ERROR));
    }
    public function send(string $method,string $url, #[\SensitiveParameter] string $body = ''): Response
    {
        $method = strtoupper($method);
        if (!in_array($method,['GET','HEAD','OPTIONS','POST','PUT','PATCH','DELETE'],true)) throw new \InvalidArgumentException('Unsupported HTTP method.');
        if (str_starts_with($url,'//')) throw new \InvalidArgumentException('Protocol-relative URLs are not accepted.');
        if (!preg_match('~^[a-z][a-z0-9+.-]*:~i',$url)) { if ($this->base==='') throw new \InvalidArgumentException('A relative URL requires baseUrl().'); $url=$this->base.'/'.ltrim($url,'/'); }
        self::validateUrl($url);
        if ($this->base!=='' && self::origin($url)!==self::origin($this->base)) throw new \InvalidArgumentException('A base URL request cannot change origin. Use a separate client for another provider.');
        $request = new Request($method,$url,$this->headers,$body,$this->timeout,$this->connectTimeout,$this->maxBytes);
        $attempts = in_array($method,['GET','HEAD','OPTIONS'],true)||$this->retryUnsafe?$this->attempts:1;
        for ($attempt=1;;$attempt++) {
            try {
                $response=$this->transport->send($request);
                if (strlen($response->body)>$this->maxBytes) throw new \LengthException('The HTTP response exceeds the configured size limit.');
                if ($attempt>=$attempts || !in_array($response->status,[429,502,503,504],true)) return $response;
                $after=$response->header('Retry-After');$wait=$this->delay;
                if($after!==null){
                    if(ctype_digit($after))$seconds=(float)$after;
                    else{$date=\DateTimeImmutable::createFromFormat(DATE_RFC7231,$after,new \DateTimeZone('GMT'));$seconds=$date===false?0:max(0,$date->getTimestamp()-time());}
                    // Do not retry earlier than a long server-requested cooldown.
                    if($seconds>10)return $response;
                    $wait=(int)ceil($seconds*1000);
                }
            } catch (ConnectionException $error) { if ($attempt>=$attempts) throw $error; $wait=$this->delay; }
            if ($wait>0) usleep($wait*1000);
        }
    }
    public function __debugInfo(): array { return ['origin'=>$this->base===''?'':self::origin($this->base),'headers'=>array_keys($this->headers),'timeout'=>$this->timeout]; }
}
