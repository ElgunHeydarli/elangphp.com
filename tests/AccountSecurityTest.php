<?php
declare(strict_types=1);
namespace Tests;
use Elang\{Auth\Users,Auth\Auth,Auth\AuthProvider,Auth\ApiIdentity,Auth\RequireScope,Database\Database,Security\Csrf,Security\Account\Mfa,Security\Account\Passkeys,Security\Account\Devices};
use Symfony\Component\HttpFoundation\{Request,Session\Session,Session\Storage\MockArraySessionStorage};
final class AccountSecurityTest extends TestCase
{
    private function app():\Elang\Application
    {
        mkdir($this->temporary.'/resources/views',0770,true);$app=$this->application(['debug'=>true,'url'=>'http://localhost','key'=>base64_encode(random_bytes(32))]);\Elang\Auth\Schema::up($app->container->get(Database::class)->connection());$app->provider(new AuthProvider());$app->container->instance(\Psr\Clock\ClockInterface::class,new \Symfony\Component\Clock\MockClock('2026-10-29 12:00:00 UTC'));return $app;
    }
    private function request(Session $session,string $path='/',string $method='GET',array $data=[]):Request { $r=Request::create('http://localhost'.$path,$method,$data);$r->setSession($session);return $r; }
    public function testMfaRequiresSecondFactorAndRejectsReplayedCodes():void
    {
        $app=$this->app();$user=$app->container->get(Users::class)->create('Ada','ada@example.test','A unique account password','user',true);$session=new Session(new MockArraySessionStorage());$r=$this->request($session);$mfa=$app->container->get(Mfa::class);$clock=$app->container->get(\Psr\Clock\ClockInterface::class);$setup=$mfa->begin($r,$user);$otp=\OTPHP\TOTP::createFromSecret($setup['secret'],$clock);$codes=$mfa->enable($r,$user,$otp->now());self::assertCount(10,$codes);self::assertFalse($mfa->verify($user,$otp->now()));$db=$app->container->get(Database::class)->connection();self::assertStringNotContainsString($setup['secret'],$db->fetchOne('SELECT secret FROM auth_mfa'));
        $auth=$app->container->get(Auth::class);self::assertTrue($auth->attempt($r,$user->email,'A unique account password'));self::assertNull($auth->user($r));self::assertSame($user->id,$auth->pending($r)->id);self::assertFalse($auth->secondFactor($r,'000000'));$clock->sleep(30);self::assertTrue($auth->secondFactor($r,$otp->now()));self::assertSame($user->id,$auth->user($r)->id);self::assertFalse($mfa->verify($user,$otp->now()));self::assertTrue($mfa->verify($user,$codes[0]));self::assertFalse($mfa->verify($user,$codes[0]));
    }
    public function testDeviceRevocationAndSecurityPageArePrivate():void
    {
        $app=$this->app();$user=$app->container->get(Users::class)->create('Ada','ada@example.test','A unique account password','user',true);$one=new Session(new MockArraySessionStorage());$two=new Session(new MockArraySessionStorage());$auth=$app->container->get(Auth::class);$auth->login($this->request($one),$user);$auth->login($this->request($two),$user);$devices=$app->container->get(Devices::class);self::assertCount(2,$devices->list($user,$this->request($one)));$hash=$two->get('_auth')['device'];self::assertTrue($devices->revoke($user,$hash));self::assertNull($auth->user($this->request($two)));self::assertNotNull($auth->user($this->request($one)));$page=$app->handle($this->request($one,'/security'));self::assertSame(200,$page->getStatusCode(),$page->getContent());self::assertStringContainsString('no-store',$page->headers->get('Cache-Control'));self::assertStringContainsString('Account security',$page->getContent());
    }
    public function testSessionApiRotatesCsrfAndRequiresItForCookieWrites():void
    {
        $app=$this->app();$user=$app->container->get(Users::class)->create('Ada','ada@example.test','A unique account password','user',true);$session=new Session(new MockArraySessionStorage());$show=json_decode($app->handle($this->request($session,'/api/session'))->getContent(),true);self::assertNull($show['user']);$login=$this->request($session,'/api/session/login','POST',['email'=>$user->email,'password'=>'A unique account password','_token'=>$show['csrfToken']]);$response=$app->handle($login);self::assertSame(200,$response->getStatusCode(),$response->getContent());$state=json_decode($response->getContent(),true);self::assertNotSame($show['csrfToken'],$state['csrfToken']);
        $app->router->post('/api/protected',fn()=>['written'=>true])->middleware(\Elang\Http\Middleware\SessionMiddleware::class,ApiIdentity::class,RequireScope::class)->defaults(['_scope'=>'notes:write']);$write=$this->request($session,'/api/protected','POST');self::assertSame(419,$app->handle($write)->getStatusCode());$write=$this->request($session,'/api/protected','POST');$write->headers->set('X-CSRF-Token',$state['csrfToken']);self::assertSame(200,$app->handle($write)->getStatusCode());$write->headers->set('Authorization','Bearer invalid');self::assertSame(401,$app->handle($write)->getStatusCode());
    }
    public function testPasskeyChallengesExpireAndAreConsumedOnBadCredentials():void
    {
        $app=$this->app();$session=new Session(new MockArraySessionStorage());$request=$this->request($session);$passkeys=$app->container->get(Passkeys::class);$options=$passkeys->authenticationOptions($request);self::assertSame('required',$options['userVerification']);self::assertSame('localhost',$options['rpId']);self::assertNull($passkeys->authenticate($request,['id'=>'invalid']));self::assertFalse($session->has('_passkey_challenge'));$this->expectException(\Elang\Http\HttpException::class);$passkeys->authenticate($request,['id'=>'invalid']);
    }
}
