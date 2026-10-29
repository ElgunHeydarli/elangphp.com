<?php
declare(strict_types=1);
namespace Tests;
use Elang\{Database\Database,Queue\Queue,Queue\Jobs,Queue\Schedule,Realtime\Events,Realtime\SocketTokens,Auth\Users};
final class QueueRealtimeTest extends TestCase
{
    private function app(array $settings=[]):\Elang\Application
    {
        $app=$this->application(array_replace_recursive(['url'=>'http://localhost','key'=>base64_encode(random_bytes(32))],$settings));$connection=$app->container->get(Database::class)->connection();\Elang\Queue\Schema::up($connection);\Elang\Realtime\Schema::up($connection);\Elang\Auth\Schema::up($connection);return $app;
    }
    public function testJobsAreReservedOnceAndRunWithInjectedDependencies():void
    {
        $app=$this->app();$jobs=$app->container->get(Jobs::class);$queue=$app->container->get(Queue::class);$seen=[];
        $jobs->register('example.capture',function(array $payload,Database $database)use(&$seen):void { $seen[]=$payload['value'];self::assertSame(1,(int)$database->connection()->fetchOne('SELECT 1')); });
        $id=$queue->push('example.capture',['value'=>42]);$claim=$queue->claim();self::assertSame($id,$claim['id']);self::assertNull($queue->claim());self::assertTrue($queue->complete($claim));self::assertFalse($queue->complete($claim));
        $queue->push('example.capture',['value'=>43]);self::assertTrue($queue->workOne());self::assertSame([43],$seen);self::assertFalse($queue->workOne());
    }
    public function testExpiredLeaseCannotAcknowledgeNewReservationAndFailuresRetry():void
    {
        $app=$this->app(['queue'=>['max_attempts'=>1]]);$jobs=$app->container->get(Jobs::class);$jobs->register('example.fail',fn()=>throw new \RuntimeException('private-secret'));$queue=$app->container->get(Queue::class);$db=$app->container->get(Database::class)->connection();
        $id=$queue->push('example.fail');$old=$queue->claim();$db->update('elang_jobs',['reserved_until'=>time()-1],['id'=>$id]);$new=$queue->claim();self::assertNotSame($old['reservation'],$new['reservation']);self::assertFalse($queue->complete($old));$queue->failed($new,new \RuntimeException('private-secret'));self::assertCount(1,$queue->failures());self::assertStringNotContainsString('private-secret',json_encode($queue->failures()));self::assertTrue($queue->retry($id));self::assertTrue($queue->workOne());self::assertCount(1,$queue->failures());
    }
    public function testRollbackContainsJobsAndEventsAndSchedulesDeduplicate():void
    {
        $app=$this->app();$db=$app->container->get(Database::class)->connection();$queue=$app->container->get(Queue::class);$events=$app->container->get(Events::class);$app->container->get(Jobs::class)->register('reports.generate',fn()=>null);
        try { $db->transactional(function()use($queue,$events):void { $queue->push('reports.generate');$events->publish('user:1:notes','note.created',['id'=>1]);throw new \RuntimeException('roll back'); }); }catch(\RuntimeException){}
        self::assertSame(0,(int)$db->fetchOne('SELECT COUNT(*) FROM elang_jobs'));self::assertSame([],$events->after('user:1:notes'));
        $schedule=$app->container->get(Schedule::class);$schedule->everyMinute('reports.minute','reports.generate');$schedule->dailyAt('reports.daily','02:00','reports.generate');$now=new \DateTimeImmutable('2026-10-29 02:00:00+00:00');self::assertSame(2,$schedule->runDue($now));self::assertSame(0,$schedule->runDue($now));self::assertSame(1,$schedule->runDue($now->modify('+1 minute')));
    }
    public function testPrivateEventReplayAndSocketPermissionsExpireOnPasswordChange():void
    {
        $app=$this->app();$events=$app->container->get(Events::class);$one=$events->publish('user:1:notes','note.created',['id'=>1]);$events->publish('user:2:notes','note.created',['id'=>2]);$three=$events->publish('user:1:notes','note.created',['id'=>3]);$rows=$events->after('user:1:notes',$one);self::assertCount(1,$rows);self::assertSame($three,$rows[0]['id']);self::assertStringContainsString('event: note.created',\Elang\Realtime\Stream::frame($rows[0]));
        $users=$app->container->get(Users::class);$user=$users->create('Member','member@example.com','Long unique passphrase 123','user',true);$tokens=$app->container->get(SocketTokens::class);$token=$tokens->issue($user,'user:1:notes',300,['note.create']);self::assertSame(['note.create'],$tokens->verify($token)['commands']);self::assertNull($tokens->verify($token.'tampered'));
        $messages=$app->container->get(\Elang\Realtime\Messages::class);$messages->register('note.create',fn(array $payload)=>$payload);self::assertSame(['id'=>1],$messages->handle('note.create',['id'=>1],$user,'user:1:notes',['note.create']));
        $users->changePassword($user,'A replacement passphrase 456');self::assertNull($tokens->verify($token));
        $this->expectException(\Elang\Http\HttpException::class);$messages->handle('note.create',[],$user,'user:1:notes',[]);
    }
    public function testUnknownJobsCannotExecutePayloadClassNames():void
    {
        $this->expectException(\InvalidArgumentException::class);$this->app()->container->get(Queue::class)->push('shell.exec',['class'=>'System','command'=>'whoami']);
    }
}
