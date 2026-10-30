<?php
declare(strict_types=1);
namespace Tests;
use Elang\Http\Client\{Client,FakeTransport,Response,Request,RequestException,GuzzleTransport};
final class HttpClientTest extends TestCase
{
    public function testJsonQueryAndCredentialSettingsAreScopedToOnePendingRequest():void
    {
        $fake=(new FakeTransport())->when('https://api.example.test/*',new Response(200,'{"data":{"ok":true}}'));
        $client=new Client($fake);self::assertTrue($client->baseUrl('https://api.example.test/v1')->withToken('not-a-real-token')->post('/records',['title'=>'A'])->json('data.ok'));
        $client->get('https://api.example.test/search',['q'=>'A & B']);
        $requests=$fake->requests();self::assertSame('Bearer not-a-real-token',$requests[0]->header('Authorization'));self::assertSame(['title'=>'A'],json_decode($requests[0]->body,true));self::assertNull($requests[1]->header('Authorization'));self::assertStringContainsString('q=A%20%26%20B',$requests[1]->url);
        $fake->assertSentCount(2);$fake->assertSent(fn(Request $request)=>$request->method==='POST');
    }
    public function testSafeRetriesAndLongCooldownsDoNotReplayWrites():void
    {
        $fake=(new FakeTransport())->sequence('https://api.example.test/read',new Response(503),new Response(200,'{}'))->when('https://api.example.test/write',new Response(503))->when('https://api.example.test/cooldown',new Response(429,'',['Retry-After'=>'600']));$client=new Client($fake);
        self::assertSame(200,$client->retry(3)->get('https://api.example.test/read')->status);
        self::assertSame(503,$client->retry(3)->post('https://api.example.test/write')->status);
        self::assertSame(429,$client->retry(3)->get('https://api.example.test/cooldown')->status);$fake->assertSentCount(4);
    }
    public function testUnexpectedFakeRequestsCannotReachTheNetwork():void { $this->expectException(\LogicException::class);(new Client(new FakeTransport()))->get('https://never-contact.example.test'); }
    public function testBaseUrlCannotLeakAuthorizationToAnotherOrigin():void
    {
        $fake=new FakeTransport();try{(new Client($fake))->baseUrl('https://api.example.test')->withToken('not-a-real-token')->get('https://elsewhere.example.test');self::fail('Origin override accepted');}catch(\InvalidArgumentException){}$fake->assertSentCount(0);
    }
    public function testDebugOutputAndHttpErrorsDoNotExposeCredentialsOrBodies():void
    {
        $request=new Request('POST','https://api.example.test/login?token=private-query',['authorization'=>'private-token'],'private-body');ob_start();var_dump($request);$debug=ob_get_clean();self::assertStringNotContainsString('private-query',$debug);self::assertStringNotContainsString('private-token',$debug);self::assertStringNotContainsString('private-body',$debug);
        try{(new Response(422,'private-body'))->throw();self::fail('Missing error');}catch(RequestException $error){self::assertStringNotContainsString('private-body',$error->getMessage());self::assertSame(422,$error->response->status);}
    }
    public function testRealGuzzleTransportKeepsTlsAndRedirectControlsAndBoundsTheBody():void
    {
        $options=[];$handler=function($request,$received)use(&$options){$options=$received;return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(302,['Location'=>'https://elsewhere.example.test','Content-Length'=>'8'],'abcdefgh'));};
        $guzzle=new \GuzzleHttp\Client(['handler'=>$handler]);$transport=new GuzzleTransport($guzzle);
        self::assertSame(302,$transport->send(new Request('GET','https://api.example.test',[],'',3,2,16))->status);self::assertTrue($options['verify']);self::assertFalse($options['allow_redirects']);self::assertSame(3.0,$options['read_timeout']);
        $this->expectException(\LengthException::class);$transport->send(new Request('GET','https://api.example.test',[],'',3,2,4));
    }
    public function testInvalidHeaderIsRejectedBeforeAnyTransportCall():void { $this->expectException(\InvalidArgumentException::class);(new Client(new FakeTransport()))->withHeaders(['X-Test'=>"one\r\nBcc: two"]); }
}
