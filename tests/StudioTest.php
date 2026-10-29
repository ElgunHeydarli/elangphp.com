<?php
declare(strict_types=1);
namespace Tests;
use Symfony\Component\HttpFoundation\Request;
final class StudioTest extends TestCase
{
    public function testLocalTraceRedactsValuesAndCapturesQueryTiming(): void
    {
        mkdir($this->temporary.'/resources/views',0770,true);$app=$this->application(['environment'=>'local']);
        $app->router->get('/trace/{token}',function()use($app):array { $app->container->get(\Elang\Database\Database::class)->connection()->executeQuery('SELECT ? AS secret',["private-password-value"])->fetchOne();return ['ok'=>true]; });
        $response=$app->handle(Request::create('/trace/'.str_repeat('a',64).'?secret=private-query-value'));$id=$response->headers->get('X-Elang-Trace');$json=file_get_contents($this->temporary.'/storage/studio/'.$id.'.json');
        self::assertStringNotContainsString('private-password-value',$json);self::assertStringNotContainsString('private-query-value',$json);self::assertStringNotContainsString(str_repeat('a',64),$json);$trace=json_decode($json,true);self::assertGreaterThanOrEqual(0,$trace['queries'][0]['milliseconds']);self::assertSame(200,$trace['status']);
        $request=Request::create('http://localhost/_elang/studio');$request->server->set('REMOTE_ADDR','127.0.0.1');self::assertSame(200,$app->handle($request)->getStatusCode());$request=Request::create('http://attacker.example/_elang/studio');$request->server->set('REMOTE_ADDR','127.0.0.1');self::assertSame(404,$app->handle($request)->getStatusCode());$request=Request::create('http://localhost/_elang/studio');$request->server->set('REMOTE_ADDR','203.0.113.2');self::assertSame(404,$app->handle($request)->getStatusCode());
    }
    public function testProductionDoesNotRegisterStudioOrPersistTraces(): void
    {
        $app=$this->application(['environment'=>'production']);$request=Request::create('http://localhost/_elang/studio');$request->server->set('REMOTE_ADDR','127.0.0.1');$response=$app->handle($request);self::assertSame(404,$response->getStatusCode());self::assertFalse($response->headers->has('X-Elang-Trace'));self::assertDirectoryDoesNotExist($this->temporary.'/storage/studio');
    }
}
