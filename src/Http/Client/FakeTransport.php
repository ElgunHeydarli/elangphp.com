<?php
declare(strict_types=1);
namespace Elang\Http\Client;

/** Scoped to one injected client. Unexpected requests never reach the network. */
final class FakeTransport implements Transport
{
    private array $rules = [];
    private array $requests = [];
    public function when(string $pattern, Response|callable $response): self { $this->rules[] = [$pattern,$response]; return $this; }
    public function sequence(string $pattern, Response ...$responses): self
    {
        $position=0;return $this->when($pattern,function()use($responses,&$position):Response { if(!isset($responses[$position])) throw new \LogicException('The fake HTTP response sequence is exhausted.'); return $responses[$position++]; });
    }
    public function send(Request $request): Response
    {
        $this->requests[]=$request;
        foreach ($this->rules as [$pattern,$response]) {
            $regex='~^'.str_replace('\\*','.*',preg_quote($pattern,'~')).'$~D';
            if (preg_match($regex,$request->url)) {
                $result=$response instanceof Response?$response:$response($request);
                if (!$result instanceof Response) throw new \LogicException('A fake HTTP handler must return an HTTP client Response.'); return $result;
            }
        }
        throw new \LogicException('Unexpected outbound request; register a matching fake response.');
    }
    public function requests(): array { return $this->requests; }
    public function assertSentCount(int $count): void { \Elang\Testing\Assert::same($count,count($this->requests),'Unexpected outbound HTTP request count.'); }
    public function assertSent(callable $predicate): void { $matched=false;foreach($this->requests as $request)if($predicate($request)){$matched=true;break;}\Elang\Testing\Assert::truth($matched,'The expected outbound HTTP request was not recorded.'); }
    public function __debugInfo(): array { return ['rules'=>count($this->rules),'recorded_requests'=>count($this->requests)]; }
}
