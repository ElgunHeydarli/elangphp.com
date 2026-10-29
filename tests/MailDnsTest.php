<?php
declare(strict_types=1);
namespace Tests;

use App\Integrations\MailDns;
use GuzzleHttp\{Client,Handler\MockHandler,HandlerStack,Middleware};
use GuzzleHttp\Psr7\Response;
use Elang\Auth\{Auth,Users};
use Elang\Database\{Database,Migrator};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\{Session,Storage\MockArraySessionStorage};

final class MailDnsTest extends TestCase
{
    private function service(array $responses,array &$history):MailDns
    {
        mkdir($this->temporary.'/config');copy(dirname(__DIR__).'/config/mail-dns.json',$this->temporary.'/config/mail-dns.json');
        $stack=HandlerStack::create(new MockHandler($responses));$stack->push(Middleware::history($history));
        return new MailDns($this->application(),new Client(['handler'=>$stack]));
    }
    public function testOnlyReviewedMissingRecordsAreCreatedAndKeyStaysOutOfUrlsAndStorage():void
    {
        $history=[];$service=$this->service([new Response(200,[],'[]'),new Response(201),new Response(201),new Response(201)],$history);
        $key='a-private-fornex-test-key';self::assertSame(['created'=>3,'present'=>0],$service->publish($key));self::assertCount(4,$history);
        foreach($history as $index=>$item) {
            $request=$item['request'];self::assertSame('https://fornex.com/api/dns/domain/elangphp.com/entry_set/',(string)$request->getUri());self::assertSame('Api-Key '.$key,$request->getHeaderLine('Authorization'));self::assertFalse($item['options']['allow_redirects']);
            if($index===0) self::assertSame('GET',$request->getMethod());else { self::assertSame('POST',$request->getMethod());self::assertSame($service->records()[$index-1],json_decode((string)$request->getBody(),true)); }
        }
        self::assertDirectoryDoesNotExist($this->temporary.'/storage/integrations');
    }
    public function testExistingMailRecordsAreIdempotentAndUnrelatedTxtIsPreserved():void
    {
        $records=json_decode(file_get_contents(dirname(__DIR__).'/config/mail-dns.json'),true);$records[0]['host']='@';$records[1]['host']='mail._domainkey.elangphp.com.';$records[]=['host'=>'','type'=>'TXT','value'=>'google-site-verification=keep-this-value'];
        $history=[];$service=$this->service([new Response(200,[],json_encode($records))],$history);self::assertSame(['created'=>0,'present'=>3],$service->publish('a-private-fornex-test-key'));self::assertCount(1,$history);
    }
    public function testAllConflictsAreCheckedBeforeAnyExternalWrite():void
    {
        $history=[];$service=$this->service([new Response(200,[],json_encode([['host'=>'_dmarc','type'=>'TXT','value'=>'v=DMARC1; p=reject']]))],$history);
        try { $service->publish('a-private-fornex-test-key');self::fail('Existing policy must be preserved.'); }catch(\RuntimeException $error) { self::assertStringContainsString('no records were changed',$error->getMessage()); }
        self::assertCount(1,$history);
    }
    public function testCnameAndExistingSpfPoliciesCannotBeOverwritten():void
    {
        $history=[];$service=$this->service([new Response(200,[],json_encode([['host'=>'mail._domainkey','type'=>'CNAME','value'=>'existing-provider.example.']])),new Response(200,[],json_encode([['host'=>'','type'=>'TXT','value'=>'v=spf1 include:existing-provider.example -all']]))],$history);
        for($i=0;$i<2;$i++) { try { $service->publish('a-private-fornex-test-key');self::fail('A conflict must stop publication.'); }catch(\RuntimeException $error) { self::assertStringContainsString('conflict',$error->getMessage()); } }
        self::assertCount(2,$history);
    }
    public function testErrorsNeverReturnProviderResponseBodiesOrCredentials():void
    {
        $history=[];$secret='a-private-fornex-test-key';$service=$this->service([new Response(403,[],$secret),new Response(200,[],'[]'),new Response(201),new Response(503,[],$secret)],$history);
        for($i=0;$i<2;$i++) { try { $service->publish($secret);self::fail('The provider operation must fail.'); }catch(\RuntimeException $error) { self::assertStringNotContainsString($secret,$error->getMessage());self::assertStringContainsString($i===0?'rejected':'may already have been created',$error->getMessage()); } }
        self::assertCount(4,$history);
    }
    public function testPublicationRequiresAdministratorCsrfHttpsAndCurrentPassword():void
    {
        $app=new \Elang\Application(dirname(__DIR__),['environment'=>'testing','paths'=>['storage'=>$this->temporary.'/storage'],'database'=>['url'=>'sqlite:///:memory:'],'key'=>base64_encode(random_bytes(32)),'url'=>'https://elangphp.com','mail'=>['dsn'=>'null://null']]);
        $history=[];$stack=HandlerStack::create(new MockHandler([new Response(200,[],'[]'),new Response(201),new Response(201),new Response(201)]));$stack->push(Middleware::history($history));
        $app->provider(new \App\Providers\SiteProvider());$app->container->instance(\GuzzleHttp\ClientInterface::class,new Client(['handler'=>$stack]));$router=$app->router;require dirname(__DIR__).'/routes/web.php';$app->container->get(Migrator::class)->migrate();
        $session=new Session(new MockArraySessionStorage());
        $request=function(string $method='GET',array $data=[],bool $secure=true)use($session):Request { $r=Request::create(($secure?'https':'http').'://elangphp.com/admin/integrations'.($method==='POST'?'/mail-dns':''),$method,$data);$r->setSession($session);return $r; };
        self::assertSame(302,$app->handle($request())->getStatusCode());
        $users=$app->container->get(Users::class);$reader=$users->create('Reader','reader@example.test','A trusted test password 123','user',true);$app->container->get(Auth::class)->login($request(),$reader);self::assertSame(403,$app->handle($request())->getStatusCode());
        $admin=$users->create('Admin','admin@example.test','A trusted test password 123','admin',true);$app->container->get(Auth::class)->login($request(),$admin);
        $page=$app->handle($request());self::assertSame(200,$page->getStatusCode());self::assertStringContainsString('id="mail-dns"',$page->getContent());self::assertStringContainsString('no-store',$page->headers->get('Cache-Control'));
        self::assertSame(419,$app->handle($request('POST',['fornex_api_key'=>'a-private-fornex-test-key']))->getStatusCode());
        $data=['_token'=>$session->get('_csrf'),'fornex_api_key'=>'a-private-fornex-test-key','current_password'=>'wrong'];self::assertSame(422,$app->handle($request('POST',$data))->getStatusCode());
        $data['current_password']='A trusted test password 123';self::assertSame(403,$app->handle($request('POST',$data,false))->getStatusCode());self::assertCount(0,$history);
        $response=$app->handle($request('POST',$data));self::assertSame(303,$response->getStatusCode());self::assertSame('/admin/integrations?mail_dns=submitted#mail-dns',$response->headers->get('Location'));self::assertCount(4,$history);
        self::assertDirectoryDoesNotExist($this->temporary.'/storage/integrations');self::assertSame(1,(int)$app->container->get(Database::class)->connection()->fetchOne('SELECT COUNT(*) FROM elang_audit WHERE action = ?',['integration.mail_dns_submitted']));
    }
}
