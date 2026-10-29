<?php
declare(strict_types=1);
namespace Tests;
use Elang\{Auth\Users,Database\Database,Tenancy\Teams,Storage\Files,Notification\Notifications,Notification\Provider,Audit\Audit,Http\HttpException,Queue\Queue};
final class PlatformTest extends TestCase
{
    private function app(array $config=[]):\Elang\Application
    {
        $app=$this->application(array_replace_recursive(['url'=>'http://localhost','key'=>base64_encode(random_bytes(32))],$config));$db=$app->container->get(Database::class)->connection();foreach([\Elang\Auth\Schema::class,\Elang\Queue\Schema::class,\Elang\Tenancy\Schema::class,\Elang\Storage\Schema::class,\Elang\Notification\Schema::class,\Elang\Observability\Schema::class] as $schema) $schema::up($db);$app->provider(new Provider());return $app;
    }
    private function denied(callable $action,int $status):void { try { $action();self::fail('Expected denial'); }catch(HttpException $e) { self::assertSame($status,$e->status); } }
    public function testTeamIsolationInvitationsAndRoleBoundaries():void
    {
        $app=$this->app();$users=$app->container->get(Users::class);$owner=$users->create('Owner','owner@example.test','A unique account password','user',true);$viewer=$users->create('Viewer','viewer@example.test','A second account password','user',true);$other=$users->create('Other','other@example.test','A third account password','user',true);$teams=$app->container->get(Teams::class);$team=$teams->create($owner,'Product');
        self::assertSame([],$teams->list($other));$this->denied(fn()=>$teams->members($other,$team['id']),404);$invite=$teams->invite($owner,$team['id'],$viewer->email,'viewer');self::assertNull($teams->accept($other,$invite));self::assertNotNull($teams->accept($viewer,$invite));self::assertNull($teams->accept($viewer,$invite));self::assertCount(2,$teams->members($viewer,$team['id']));
        $this->denied(fn()=>$teams->within($viewer,$team['id'],'write',fn()=>null),403);$this->denied(fn()=>$teams->role($owner,$team['id'],$owner->id,'member'),409);$this->denied(fn()=>$teams->remove($owner,$team['id'],$owner->id),403);
        $teams->role($owner,$team['id'],$viewer->id,'admin');$this->denied(fn()=>$teams->invite($viewer,$team['id'],$other->email,'admin'),403);$teams->transfer($owner,$team['id'],$viewer->id);$this->denied(fn()=>$teams->transfer($owner,$team['id'],$viewer->id),403);self::assertTrue($teams->remove($viewer,$team['id'],$owner->id));$this->denied(fn()=>$teams->members($owner,$team['id']),404);
        self::assertTrue($app->container->get(Audit::class)->verify('team:'.$team['id'])['valid']);
    }
    public function testFilesStayPrivateAndRevocationOverridesSignedUrls():void
    {
        $app=$this->app();$users=$app->container->get(Users::class);$owner=$users->create('Owner','owner@example.test','A unique account password','user',true);$member=$users->create('Member','member@example.test','A second account password','user',true);$files=$app->container->get(Files::class);$teams=$app->container->get(Teams::class);$team=$teams->create($owner,'Files');$teams->accept($member,$teams->invite($owner,$team['id'],$member->email));
        $personal=$files->put($owner,'notes.txt','Private document');self::assertArrayNotHasKey('object_key',$personal);$this->denied(fn()=>$files->download($member,$personal['id']),404);
        $shared=$files->put($owner,'team.txt','Team document',$team['id']);$url=$files->temporaryUrl($member,$shared['id']);parse_str(parse_url($url,PHP_URL_QUERY),$query);self::assertTrue($files->verifyUrl($member,$shared['id'],(int)$query['expires'],$query['signature']));self::assertFalse($files->verifyUrl($owner,$shared['id'],(int)$query['expires'],$query['signature']));$download=$files->download($member,$shared['id']);self::assertSame('Team document',stream_get_contents($download['stream']));fclose($download['stream']);$teams->remove($owner,$team['id'],$member->id);$this->denied(fn()=>$files->download($member,$shared['id']),404);
        $files->delete($owner,$personal['id']);$this->denied(fn()=>$files->download($owner,$personal['id']),404);$this->denied(fn()=>$files->download($owner,'../../.env'),404);
    }
    public function testRasterImagesAreDecodedReencodedAndBounded():void
    {
        if(!extension_loaded('gd')) self::markTestSkipped('GD image processing is optional.');$app=$this->app();$user=$app->container->get(Users::class)->create('Owner','owner@example.test','A unique account password','user',true);$files=$app->container->get(Files::class);$image=imagecreatetruecolor(4,4);ob_start();imagepng($image);$bytes=ob_get_clean();imagedestroy($image);$row=$files->put($user,'avatar.png',$bytes.'<script>payload</script>',null,true);$download=$files->download($user,$row['id']);$safe=stream_get_contents($download['stream']);fclose($download['stream']);self::assertStringNotContainsString('<script>',$safe);self::assertSame('image/png',$row['mime']);$this->denied(fn()=>$files->put($user,'x.svg','<svg/>',null,true),422);
    }
    public function testNotificationsQueueMailWithoutLeakingEmailOrBodyAndRespectOwnership():void
    {
        $app=$this->app(['mail'=>['dsn'=>'smtp://127.0.0.1:1025']]);$users=$app->container->get(Users::class);$user=$users->create('Owner','owner@example.test','A unique account password','user',true);$other=$users->create('Other','other@example.test','A second account password','user',true);$service=$app->container->get(Notifications::class);$id=$service->send($user,'Welcome','A confidential message','/dashboard',true);self::assertCount(1,$service->inbox($user));self::assertSame([],$service->inbox($other));self::assertFalse($service->markRead($other,$id));self::assertTrue($service->markRead($user,$id));self::assertFalse($service->markRead($user,$id));$db=$app->container->get(Database::class)->connection();$payload=$db->fetchOne('SELECT payload FROM elang_jobs');self::assertStringNotContainsString($user->email,$payload);self::assertStringNotContainsString('confidential',$payload);
        $mailer=new class implements \Symfony\Component\Mailer\MailerInterface { public array $messages=[];public function send(\Symfony\Component\Mime\RawMessage $message,?\Symfony\Component\Mailer\Envelope $envelope=null):void { $this->messages[]=$message; } };
        $app->container->instance(\Symfony\Component\Mailer\MailerInterface::class,$mailer);self::assertTrue($app->container->get(Queue::class)->workOne());self::assertCount(1,$mailer->messages);self::assertSame(0,(int)$db->fetchOne('SELECT COUNT(*) FROM elang_jobs'));
    }
    public function testAuditDetectsTamperingAndTelemetryMetricsExcludePrivateUrls():void
    {
        $app=$this->app(['operations'=>['enabled'=>true]]);$audit=$app->container->get(Audit::class);$audit->append('test','record.created',null,['id'=>4]);self::assertTrue($audit->verify('test')['valid']);$db=$app->container->get(Database::class)->connection();$db->executeStatement('UPDATE elang_audit SET action = ?',['record.deleted']);self::assertFalse($audit->verify('test')['valid']);
        $app->router->get('/private/{secret}',fn()=>['ok'=>true],'private.read');$app->handle(\Symfony\Component\HttpFoundation\Request::create('/private/super-private-value?email=hidden'));$snapshot=$app->container->get(\Elang\Observability\Operations::class)->snapshot();self::assertSame(1,(int)$snapshot['http'][0]['requests']);self::assertSame('GET private.read',$snapshot['http'][0]['route_name']);self::assertStringNotContainsString('super-private',json_encode($snapshot));
    }
}
