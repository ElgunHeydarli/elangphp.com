<?php
declare(strict_types=1);
namespace Tests;
use Elang\Ai\{DocumentationServer,DocumentSource};
use Symfony\Component\HttpFoundation\Request;
final class McpTest extends TestCase
{
    private function server(): DocumentationServer
    {
        $source=new class implements DocumentSource {
            public function search(string $query,int $limit=10):array { return [['title'=>'Routing','url'=>'/docs/v0.1/routing']]; }
            public function read(string $path):?array { return $path==='docs/v0.1/routing'?['path'=>$path,'markdown'=>'# Routing']:null; }
            public function list():array { return [['path'=>'docs/v0.1/routing','title'=>'Routing']]; }
        };return new DocumentationServer($source,new \Elang\Support\Config(['url'=>'https://elangphp.com','environment'=>'production']));
    }
    public function testProtocolToolsAndResourcesAreReadOnlyAndBounded(): void
    {
        $server=$this->server();$call=fn($method,$params=[])=>$server->handle(['jsonrpc'=>'2.0','id'=>1,'method'=>$method,'params'=>$params]);
        self::assertSame(DocumentationServer::PROTOCOL,$call('initialize')['result']['protocolVersion']);$tools=$call('tools/list')['result']['tools'];self::assertCount(2,$tools);self::assertTrue($tools[0]['annotations']['readOnlyHint']);
        self::assertCount(1,$call('tools/call',['name'=>'search_docs','arguments'=>['query'=>'routing']])['result']['structuredContent']['results']);self::assertSame(-32602,$call('tools/call',['name'=>'search_docs','arguments'=>['query'=>'routing','limit'=>100]])['error']['code']);
        self::assertTrue($call('tools/call',['name'=>'read_doc','arguments'=>['path'=>'../../.env']])['result']['isError']);self::assertSame('elang://docs/v0.1/routing',$call('resources/list')['result']['resources'][0]['uri']);self::assertSame('# Routing',$call('resources/read',['uri'=>'elang://docs/v0.1/routing'])['result']['contents'][0]['text']);self::assertSame(-32601,$call('execute_code')['error']['code']);self::assertNull($server->handle(['jsonrpc'=>'2.0','method'=>'notifications/initialized']));
    }
    public function testHttpRejectsForeignOriginInvalidHostAndMalformedRequests(): void
    {
        $server=$this->server();$request=fn($body)=>Request::create('https://elangphp.com/mcp','POST',[],[],[],['CONTENT_TYPE'=>'application/json'],$body);
        $valid=$request('{"jsonrpc":"2.0","id":1,"method":"ping"}');self::assertSame(200,$server->http($valid)->getStatusCode());$valid->headers->set('Origin','https://evil.example');self::assertSame(403,$server->http($valid)->getStatusCode());$invalid=$request('{}');$invalid->headers->set('Host','evil.example');self::assertSame(403,$server->http($invalid)->getStatusCode());self::assertSame(400,$server->http($request('{bad'))->getStatusCode());self::assertSame(405,$server->http(Request::create('https://elangphp.com/mcp'))->getStatusCode());self::assertSame(202,$server->http($request('{"jsonrpc":"2.0","method":"notifications/initialized"}'))->getStatusCode());
    }
}
