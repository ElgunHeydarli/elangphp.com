<?php
declare(strict_types=1);
namespace Elang\Testing;
use Elang\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
final readonly class TestResponse
{
    public function __construct(public Response $response) {}
    public function status():int { return $this->response->getStatusCode(); }
    public function body():string { return (string)$this->response->getContent(); }
    public function json(?string $path=null,mixed $default=null):mixed { $data=json_decode($this->body(),true,512,JSON_THROW_ON_ERROR);return $path===null?$data:Arr::get($data,$path,$default); }
    public function assertStatus(int $status):self { Assert::same($status,$this->status(),'Unexpected HTTP response status.');return $this; }
    public function assertOk():self { return $this->assertStatus(200); }
    public function assertCreated():self { return $this->assertStatus(201); }
    public function assertNoContent():self { $this->assertStatus(204);Assert::same('',$this->body(),'Expected an empty response body.');return $this; }
    public function assertHeader(string $name,?string $value=null):self
    {
        Assert::truth($this->response->headers->has($name),'Expected response header is missing.');if($value!==null)Assert::same($value,$this->response->headers->get($name),'Unexpected response header.');return $this;
    }
    public function assertRedirect(?string $location=null):self
    {
        Assert::truth($this->response->isRedirect(),'Expected a redirect response.');if($location!==null)$this->assertHeader('Location',$location);return $this;
    }
    public function assertJsonPath(string $path,mixed $expected):self
    {
        $missing=new \stdClass();$actual=$this->json($path,$missing);Assert::truth($actual!==$missing,'Expected JSON path is missing.');Assert::same($expected,$actual,'Unexpected JSON path value.');return $this;
    }
    public function assertJsonCount(int $count,?string $path=null):self
    {
        $value=$this->json($path);Assert::truth(is_array($value),'Expected a JSON array or object.');Assert::same($count,count($value),'Unexpected JSON item count.');return $this;
    }
    public function assertSee(string $text,bool $escaped=true):self
    {
        Assert::truth(str_contains($this->body(),$escaped?htmlspecialchars($text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'):$text),'Expected text is missing from the response.');return $this;
    }
    public function assertDontSee(string $text):self { Assert::truth(!str_contains($this->body(),$text),'Unexpected text is present in the response.');return $this; }
    public function __debugInfo():array { return ['status'=>$this->status(),'body'=>'[redacted]']; }
}
