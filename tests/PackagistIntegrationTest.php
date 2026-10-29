<?php
declare(strict_types=1);
namespace Tests;
use App\Integrations\Packagist;
use GuzzleHttp\{Client,Handler\MockHandler,HandlerStack,Middleware};
use GuzzleHttp\Psr7\Response;
final class PackagistIntegrationTest extends TestCase
{
    public function testCredentialsAreEncryptedAndNeverSentInUrls():void
    {
        $app=$this->application(['key'=>base64_encode(random_bytes(32))]);mkdir($this->temporary.'/config');file_put_contents($this->temporary.'/config/distribution-repositories.json',json_encode([['name'=>'elang/framework','repository'=>'https://github.com/ElgunHeydarli/elang-framework','commit'=>str_repeat('a',40)]]));$history=[];$stack=HandlerStack::create(new MockHandler([new Response(404),new Response(201,[],json_encode(['status'=>'success']))]));$stack->push(Middleware::history($history));$service=new Packagist($app,$app->container->get(\Elang\Security\Encryption::class),new Client(['handler'=>$stack]));$private='a-private-test-token-that-must-not-leak';$service->save('ElgunHeydarli',$private);self::assertSame(['configured'=>true,'username'=>'ElgunHeydarli'],$service->settings());self::assertStringNotContainsString($private,file_get_contents($this->temporary.'/storage/integrations/packagist.sealed'));self::assertSame('submitted',$service->submit('elang/framework')['state']);self::assertCount(2,$history);self::assertStringNotContainsString($private,(string)$history[1]['request']->getUri());self::assertSame('Bearer ElgunHeydarli:'.$private,$history[1]['request']->getHeaderLine('Authorization'));self::assertSame(['repository'=>'https://github.com/ElgunHeydarli/elang-framework'],json_decode((string)$history[1]['request']->getBody(),true));
    }
    public function testAnotherRepositoryCannotBeTakenOver():void
    {
        $app=$this->application(['key'=>base64_encode(random_bytes(32))]);mkdir($this->temporary.'/config');file_put_contents($this->temporary.'/config/distribution-repositories.json',json_encode([['name'=>'elang/framework','repository'=>'https://github.com/ElgunHeydarli/elang-framework','commit'=>str_repeat('a',40)]]));$service=new Packagist($app,$app->container->get(\Elang\Security\Encryption::class),new Client(['handler'=>HandlerStack::create(new MockHandler([new Response(200,[],json_encode(['package'=>['repository'=>'https://github.com/another/project']]))]))]));$service->save('ElgunHeydarli','a-private-test-token');$this->expectException(\RuntimeException::class);$this->expectExceptionMessage('another source repository');$service->submit('elang/framework');
    }
}
