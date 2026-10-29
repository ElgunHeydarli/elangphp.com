<?php
declare(strict_types=1);
namespace Tests;
use Elang\{Application,Database\Database,Queue\Jobs,Queue\Queue,Realtime\Events,Auth\Users};
#[\PHPUnit\Framework\Attributes\Group('database-integration')]
final class PortableDatabaseTest extends TestCase
{
    public function testRuntimeSchemasAndAtomicWritesOnDisposableDatabase():void
    {
        $url=getenv('ELANG_TEST_DATABASE_URL');if(!$url) self::markTestSkipped('Set ELANG_TEST_DATABASE_URL to a disposable elang_test_* database.');
        $app=$this->application(['database'=>['url'=>$url],'url'=>'http://localhost','key'=>base64_encode(random_bytes(32))]);$db=$app->container->get(Database::class)->connection();
        self::assertStringStartsWith('elang_test_',$db->getParams()['dbname']??'','Use an explicitly disposable database.');
        foreach(['auth_api_tokens','auth_tokens','auth_users','elang_events','elang_jobs','elang_schedule_runs','elang_idempotency'] as $table) if($db->createSchemaManager()->tablesExist([$table])) $db->createSchemaManager()->dropTable($table);
        \Elang\Auth\Schema::up($db);\Elang\Queue\Schema::up($db);\Elang\Realtime\Schema::up($db);\Elang\Http\Idempotency\Schema::up($db);\Elang\Queue\Schema::up($db);
        $user=$app->container->get(Users::class)->create('Integration','integration@example.com','An integration passphrase 123','user',true);self::assertGreaterThan(0,$user->id);
        $app->container->get(Jobs::class)->register('example.capture',fn()=>null);$queue=$app->container->get(Queue::class);$events=$app->container->get(Events::class);
        try { $db->transactional(function()use($queue,$events):void { $queue->push('example.capture');$events->publish('user:1:notes','note.created',['id'=>1]);throw new \RuntimeException('rollback'); }); }catch(\RuntimeException){}
        self::assertNull($queue->claim());self::assertSame([],$events->after('user:1:notes'));$events->publish('user:1:notes','note.created',['id'=>2]);self::assertSame(2,$events->after('user:1:notes')[0]['data']['id']);
        for($i=0;$i<20;$i++) $queue->push('example.capture',['number'=>$i]);
        $processes=[];for($i=0;$i<4;$i++) { $process=proc_open([PHP_BINARY,__DIR__.'/fixtures/claim-worker.php',$this->temporary.'/worker-'.$i],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$processes[]=[$process,$pipes]; }
        $claimed=[];foreach($processes as [$process,$pipes]) { $lines=trim(stream_get_contents($pipes[1]));$errors=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);self::assertSame(0,proc_close($process),$errors);if($lines!=='') $claimed=array_merge($claimed,explode("\n",$lines)); }
        self::assertCount(20,$claimed);self::assertCount(20,array_unique($claimed));self::assertSame(0,(int)$db->fetchOne('SELECT COUNT(*) FROM elang_jobs'));
        \Elang\Auth\Schema::down($db);foreach(['elang_events','elang_jobs','elang_schedule_runs','elang_idempotency'] as $table) $db->createSchemaManager()->dropTable($table);
    }
}
