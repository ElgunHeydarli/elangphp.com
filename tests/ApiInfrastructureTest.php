<?php
declare(strict_types=1);
namespace Tests;
use Elang\{Auth\User,Database\Database,Http\Response,Http\Middleware\Cors,Http\Idempotency\Idempotency};
use Symfony\Component\HttpFoundation\Request;
final class ApiInfrastructureTest extends TestCase
{
    public function testCorsPreflightAndErrorsWorkForAllowedOrigins():void
    {
        $app=$this->application(['cors'=>['origins'=>['https://frontend.example']]]);$app->middleware(Cors::class);$app->router->get('/api/private',fn()=>throw new \Elang\Http\HttpException(401,'Sign in first.'));
        $request=Request::create('/api/private','OPTIONS');$request->headers->add(['Origin'=>'https://frontend.example','Access-Control-Request-Method'=>'POST','Access-Control-Request-Headers'=>'authorization, content-type, idempotency-key']);$response=$app->handle($request);self::assertSame(204,$response->getStatusCode());self::assertSame('https://frontend.example',$response->headers->get('Access-Control-Allow-Origin'));self::assertFalse($response->headers->has('Access-Control-Allow-Credentials'));
        $request=Request::create('/api/private');$request->headers->set('Origin','https://frontend.example');$response=$app->handle($request);self::assertSame(401,$response->getStatusCode());self::assertSame('https://frontend.example',$response->headers->get('Access-Control-Allow-Origin'));$request->headers->set('Origin','https://evil.example');$response=$app->handle($request);self::assertSame(403,$response->getStatusCode());self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }
    public function testSameOriginBrowserFormsDoNotNeedACrossOriginAllowlist():void
    {
        $app=$this->application();$app->middleware(Cors::class);$app->router->post('/login',fn()=>['accepted'=>true]);$request=Request::create('http://localhost/login','POST');$request->headers->set('Origin','http://localhost');self::assertSame(200,$app->handle($request)->getStatusCode());$request->headers->set('Origin','http://other.example');self::assertSame(403,$app->handle($request)->getStatusCode());
    }
    public function testIdempotencyReplaysOnlyMatchingWritesAndRollsBackFailures():void
    {
        $app=$this->application();$db=$app->container->get(Database::class)->connection();\Elang\Http\Idempotency\Schema::up($db);$db->executeStatement('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT)');$middleware=$app->container->get(Idempotency::class);
        $request=function(string $title,int $owner=1):Request { $request=Request::create('/api/items','POST',[],[],[],['CONTENT_TYPE'=>'application/json'],json_encode(['title'=>$title]));$request->headers->set('Idempotency-Key','same-operation-123');$request->attributes->set('_user',new User($owner,'Member','member@example.com','user',true,1,false));return $request; };
        $handler=function()use($db) { $db->insert('items',['title'=>'one']);return Response::json(['id'=>(int)$db->lastInsertId()],201,['Location'=>'/api/items/1']); };
        $first=$middleware->process($request('one'),$handler);$repeat=$middleware->process($request('one'),$handler);self::assertSame($first->getContent(),$repeat->getContent());self::assertSame('true',$repeat->headers->get('Idempotency-Replayed'));self::assertSame(1,(int)$db->fetchOne('SELECT COUNT(*) FROM items'));
        $middleware->process($request('one',2),$handler);self::assertSame(2,(int)$db->fetchOne('SELECT COUNT(*) FROM items'));
        try { $middleware->process($request('different'),$handler);self::fail('Conflicting input was accepted.'); } catch(\Elang\Http\HttpException $error) { self::assertSame(409,$error->status); }
        try { $middleware->process($request('fail',3),function()use($db) { $db->insert('items',['title'=>'rolled back']);throw new \RuntimeException('failure'); }); }catch(\RuntimeException){}
        self::assertSame(2,(int)$db->fetchOne('SELECT COUNT(*) FROM items'));self::assertSame(2,(int)$db->fetchOne('SELECT COUNT(*) FROM elang_idempotency'));
    }
}
