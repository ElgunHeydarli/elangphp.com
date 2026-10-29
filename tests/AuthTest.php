<?php
declare(strict_types=1);
namespace Tests;
use Elang\Auth\{Schema,Users,Auth,Recovery,LinkTokens,ApiTokens,AuthProvider};
use Elang\Database\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
final class AuthTest extends TestCase
{
    private function app(): \Elang\Application
    {
        mkdir($this->temporary.'/resources/views',0770,true);
        $app=$this->application(['environment'=>'local','url'=>'http://localhost','key'=>base64_encode(random_bytes(32))]);
        Schema::up($app->container->get(Database::class)->connection());$app->provider(new AuthProvider());return $app;
    }
    private function request(string $path,Session $session,string $method='GET',array $data=[]): Request
    {
        $request=Request::create('http://localhost'.$path,$method,$data);$request->setSession($session);return $request;
    }
    public function testRegistrationLoginAndSessionRevocation(): void
    {
        $app=$this->app();$users=$app->container->get(Users::class);$auth=$app->container->get(Auth::class);
        $user=$users->register('Ada','ADA@example.com','A long unique passphrase 123');self::assertSame('ada@example.com',$user->email);self::assertSame('user',$user->role);
        $one=new Session(new MockArraySessionStorage());$two=new Session(new MockArraySessionStorage());
        $first=$this->request('/login',$one);$second=$this->request('/login',$two);
        self::assertTrue($auth->attempt($first,$user->email,'A long unique passphrase 123'));
        self::assertTrue($auth->attempt($second,$user->email,'A long unique passphrase 123'));
        self::assertSame($user->id,$auth->user($first)->id);
        $users->changePassword($user,'A different unique passphrase 456');
        self::assertNull($auth->user($first));self::assertNull($auth->user($second));
    }
    public function testLoginThrottleAndGenericFailure(): void
    {
        $app=$this->app();$request=$this->request('/login',new Session(new MockArraySessionStorage()));$auth=$app->container->get(Auth::class);
        for($i=0;$i<5;$i++) self::assertFalse($auth->attempt($request,'unknown@example.com','incorrect password'));
        $this->expectException(\Elang\Http\HttpException::class);$this->expectExceptionMessage('Too many attempts');$auth->attempt($request,'unknown@example.com','incorrect password');
    }
    public function testResetTokensAreHashedSingleUseAndRevokeApiTokens(): void
    {
        $app=$this->app();$users=$app->container->get(Users::class);$user=$users->create('Ada','ada@example.com','A long unique passphrase 123','user',true);
        $api=$app->container->get(ApiTokens::class);$access=$api->issue($user,'test',['notes:read']);
        $token=$app->container->get(LinkTokens::class)->issue($user,'reset');
        self::assertSame(hash('sha256',$token),$app->container->get(Database::class)->connection()->fetchOne('SELECT token_hash FROM auth_tokens'));
        $recovery=$app->container->get(Recovery::class);self::assertTrue($recovery->reset($token,'A replacement passphrase 456'));self::assertFalse($recovery->reset($token,'Yet another passphrase 789'));self::assertNull($api->authenticate($access));
    }
    public function testVerificationAndEncryptedLocalMailbox(): void
    {
        $app=$this->app();$user=$app->container->get(Users::class)->register('Ada','ada@example.com','A long unique passphrase 123');
        $recovery=$app->container->get(Recovery::class);$recovery->sendVerification($user,'127.0.0.1');
        $file=(glob($this->temporary.'/storage/mailbox/*.mail'))[0];$encrypted=file_get_contents($file);self::assertStringNotContainsString('ada@example.com',$encrypted);
        $message=json_decode($app->container->get(\Elang\Security\Encryption::class)->decrypt($encrypted),true);
        preg_match('~/verify/([a-f0-9]{64})~',$message['body'],$match);self::assertTrue($recovery->verify($match[1]));self::assertFalse($recovery->verify($match[1]));self::assertTrue($app->container->get(Users::class)->find($user->id)->verified);
    }
    public function testApiScopesExpiryAndOwnership(): void
    {
        $app=$this->app();$users=$app->container->get(Users::class);$one=$users->create('Ada','ada@example.com','A long unique passphrase 123','user',true);$two=$users->create('Grace','grace@example.com','Another long passphrase 456','user',true);
        $api=$app->container->get(ApiTokens::class);$token=$api->issue($one,'reader',['notes:read']);$access=$api->authenticate($token);self::assertTrue($access->allows('notes:read'));self::assertFalse($access->allows('notes:write'));self::assertFalse($api->revoke($two,$access->id));
        self::assertArrayNotHasKey('token_hash',$api->list($one)[0]);self::assertTrue($api->revoke($one,$access->id));self::assertNull($api->authenticate($token));
    }
    public function testPublicRegistrationCannotAssignAdminAndTemporaryPasswordsMustRotate(): void
    {
        $app=$this->app();$session=new Session(new MockArraySessionStorage());$app->handle($this->request('/register',$session));$csrf=$session->get('_csrf');
        $response=$app->handle($this->request('/register',$session,'POST',['_token'=>$csrf,'name'=>'Ada','email'=>'ada@example.com','password'=>'A long unique passphrase 123','password_confirmation'=>'A long unique passphrase 123','role'=>'admin']));
        self::assertSame(303,$response->getStatusCode());self::assertSame('user',$app->container->get(Users::class)->rowByEmail('ada@example.com')['role']);
        $admin=$app->container->get(Users::class)->create('Maintainer','maintainer@example.com','temporary-pass','admin',true,true);$app->container->get(Auth::class)->login($this->request('/',$session),$admin);
        self::assertSame('/password',$app->handle($this->request('/dashboard',$session))->headers->get('Location'));
    }
    public function testCsrfAndAuthFormsEscapeUserData(): void
    {
        $app=$this->app();$session=new Session(new MockArraySessionStorage());self::assertSame(419,$app->handle($this->request('/login',$session,'POST',['email'=>'bad','password'=>'bad']))->getStatusCode());
        $user=$app->container->get(Users::class)->create('<script>alert(1)</script>','ada@example.com','A long unique passphrase 123');$app->container->get(Auth::class)->login($this->request('/',$session),$user);
        $page=$app->handle($this->request('/dashboard',$session));self::assertSame(200,$page->getStatusCode(),$page->getContent());self::assertStringContainsString('&lt;script&gt;',$page->getContent());self::assertStringContainsString('no-store',$page->headers->get('Cache-Control'));
    }
}
