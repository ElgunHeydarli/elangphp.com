<?php
declare(strict_types=1);
namespace Tests;
use Elang\Testing\TestClient;
use Elang\Http\{Response,Client\Client,Client\Response as RemoteResponse};
use Symfony\Component\HttpFoundation\Request;
final class TestingToolsTest extends TestCase
{
    public function testClientKeepsSessionAndRunsRealCsrfChecks():void
    {
        $app=$this->application();$app->middleware(\Elang\Http\Middleware\SessionMiddleware::class,\Elang\Http\Middleware\CsrfMiddleware::class);
        $app->router->post('/form',function(Request $request){$request->getSession()->set('name',$request->request->get('name'));return Response::redirect('/state');});
        $app->router->get('/state',fn(Request $request)=>['name'=>$request->getSession()->get('name')]);$client=new TestClient($app);
        $client->post('/form',['name'=>'First'])->assertStatus(419);
        $client->withCsrf()->post('/form',['name'=>'Accepted'])->assertRedirect('/state');$client->get('/state')->assertOk()->assertJsonPath('name','Accepted');
        (new TestClient($app))->get('/state')->assertJsonPath('name',null);
    }
    public function testMailAndHttpAreInterceptedWithoutExternalTraffic():void
    {
        $app=$this->application();$client=new TestClient($app);$client->http->when('https://api.example.test/*',new RemoteResponse(200,'{"ok":true}'));
        $app->router->get('/integration',function(Client $http,\Symfony\Component\Mailer\MailerInterface $mailer){$mailer->send((new \Symfony\Component\Mime\Email())->from('from@example.test')->to('to@example.test')->subject('test')->text('test'));return $http->get('https://api.example.test/status')->json();});
        $client->get('/integration')->assertOk()->assertJsonPath('ok',true);$client->http->assertSentCount(1);$client->mail->assertSentCount(1);$client->mail->assertSentTo('to@example.test');
    }
    public function testDatabaseAssertionsHandleNullsAndBoundValues():void
    {
        $app=$this->application();$db=$app->container->get(\Elang\Database\Database::class)->connection();$db->executeStatement('CREATE TABLE checks (name TEXT,note TEXT)');$db->insert('checks',['name'=>"A' OR 1=1 --",'note'=>null]);(new TestClient($app))->assertDatabaseHas('checks',['name'=>"A' OR 1=1 --",'note'=>null]);
    }
    public function testProductionApplicationsCannotAccidentallyUseTheTestBrowser():void { $this->expectException(\LogicException::class);new TestClient($this->application(['environment'=>'production'])); }
    public function testActingAsUsesARealPersistedIdentityWithoutSharingAnotherSession():void
    {
        $app=$this->application(['key'=>base64_encode(random_bytes(32))]);$app->provider(new \Elang\Auth\AuthProvider());\Elang\Auth\Schema::up($app->container->get(\Elang\Database\Database::class)->connection());
        $user=$app->container->get(\Elang\Auth\Users::class)->create('Reader','reader@example.test','A unique test passphrase','user',true);
        $app->router->get('/private-test',fn(\Symfony\Component\HttpFoundation\Request $request)=>['id'=>$request->attributes->get('_user')->id])->middleware(\Elang\Http\Middleware\SessionMiddleware::class,\Elang\Auth\RequireAuth::class);
        $authenticated=(new TestClient($app))->actingAs($user);$guest=new TestClient($app);
        $authenticated->get('/private-test')->assertOk()->assertJsonPath('id',$user->id);$guest->get('/private-test')->assertRedirect('/login');$authenticated->get('/private-test')->assertOk();
    }
}
