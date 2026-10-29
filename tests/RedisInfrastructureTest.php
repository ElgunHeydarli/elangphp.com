<?php
declare(strict_types=1);
namespace Tests;
use Elang\{Database\Database,Infrastructure\Runtime,Infrastructure\RedisSessions,Queue\Jobs,Queue\Queue,Realtime\Events,Outbox\Outbox};
#[\PHPUnit\Framework\Attributes\Group('redis-integration')]
final class RedisInfrastructureTest extends TestCase
{
    private function app(string $prefix):\Elang\Application
    {
        $url=getenv('ELANG_TEST_REDIS_URL');if(!$url) self::markTestSkipped('Set ELANG_TEST_REDIS_URL to a disposable private Redis service.');return $this->application(['url'=>'http://localhost','key'=>base64_encode(random_bytes(32)),'redis'=>['url'=>$url,'prefix'=>$prefix]]);
    }
    private function clean(Runtime $runtime):void { $keys=$runtime->command(['KEYS',$runtime->prefix().'*']);foreach($keys as $key) $runtime->command(['DEL',$key]); }
    public function testCacheLocksSessionsAndRateLimitsAreSharedAndNamespaced():void
    {
        $prefix='elang_test_'.bin2hex(random_bytes(6));$one=$this->app($prefix);$two=$this->app($prefix);$isolated=$this->app($prefix.'_other');$r=$one->container->get(Runtime::class);
        try {
            $one->container->get(\Psr\SimpleCache\CacheInterface::class)->set('test','shared',30);self::assertSame('shared',$two->container->get(\Psr\SimpleCache\CacheInterface::class)->get('test'));self::assertNull($isolated->container->get(\Psr\SimpleCache\CacheInterface::class)->get('test'));
            $a=$r->lock('exclusive');$b=$two->container->get(Runtime::class)->lock('exclusive');self::assertTrue($a->acquire());self::assertFalse($b->acquire());$a->release();self::assertTrue($b->acquire());$b->release();
            $id=bin2hex(random_bytes(16));$first=new RedisSessions($r);self::assertTrue($first->write($id,'session-bytes'));$first->close();$second=new RedisSessions($two->container->get(Runtime::class));self::assertSame('session-bytes',$second->read($id));self::assertTrue($second->destroy($id));$second->close();self::assertFalse($first->validateId($id));
            $factory=$one->container->get(\Symfony\Component\RateLimiter\RateLimiterFactory::class);self::assertTrue($factory->create('shared-user')->consume(120)->isAccepted());self::assertFalse($two->container->get(\Symfony\Component\RateLimiter\RateLimiterFactory::class)->create('shared-user')->consume()->isAccepted());
        }finally { $this->clean($r);$this->clean($isolated->container->get(Runtime::class)); }
    }
    public function testExpiredSessionOwnerCannotOverwriteOrDeleteANewerSession():void
    {
        $app=$this->app('elang_test_'.bin2hex(random_bytes(6)));$runtime=$app->container->get(Runtime::class);$id=bin2hex(random_bytes(16));$old=new RedisSessions($runtime);$new=new RedisSessions($runtime);
        try { self::assertFalse($old->validateId('invalid'));$old->write($id,'old');$runtime->command(['PEXPIRE',$runtime->prefix().':locks:session:'.$id,'1']);usleep(10000);$new->write($id,'new');foreach(['write','destroy'] as $operation) { try { if($operation==='write') $old->write($id,'stale');else $old->destroy($id);self::fail('A stale session owner mutated the new session.'); }catch(\RuntimeException $error) { self::assertStringContainsString('expired',$error->getMessage()); } }self::assertSame('new',$runtime->command(['GET',$runtime->prefix().':session:'.$id])); }finally { $old->close();$new->close();$this->clean($runtime); }
    }
    public function testRollbackDoesNotPublishOutboxAndRelayRetriesDoNotDuplicate():void
    {
        $app=$this->app('elang_test_'.bin2hex(random_bytes(6)));$r=$app->container->get(Runtime::class);$db=$app->container->get(Database::class)->connection();\Elang\Queue\Schema::up($db);\Elang\Realtime\Schema::up($db);$app->container->get(Jobs::class)->register('test.capture',fn()=>null);$q=$app->container->get(Queue::class);$events=$app->container->get(Events::class);$outbox=$app->container->get(Outbox::class);
        try {
            try { $db->transactional(function()use($q,$events):void { $q->push('test.capture');$events->publish('test:channel','item.created',['id'=>1]);throw new \RuntimeException('rollback'); }); }catch(\RuntimeException){}
            self::assertSame(0,(int)$db->fetchOne('SELECT COUNT(*) FROM elang_outbox'));self::assertSame([],$events->after('test:channel'));$q->push('test.capture');$events->publish('test:channel','item.created',['id'=>2]);self::assertSame(2,$outbox->dispatch());$stream=$r->prefix().':{outbox}:events';self::assertSame(1,$r->command(['XLEN',$stream]));$db->executeStatement('UPDATE elang_outbox SET published_at = NULL');self::assertSame(2,$outbox->dispatch());self::assertSame(1,$r->command(['XLEN',$stream]));self::assertTrue($q->workOne());self::assertNull($q->claim());
        }finally { $this->clean($r); }
    }
}
