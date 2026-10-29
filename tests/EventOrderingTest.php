<?php
declare(strict_types=1);
namespace Tests;
#[\PHPUnit\Framework\Attributes\Group('database-integration')]
final class EventOrderingTest extends TestCase
{
    public function testChannelSequenceIsSerializedThroughBusinessCommit():void
    {
        $url=getenv('ELANG_TEST_DATABASE_URL');if(!$url) self::markTestSkipped('A disposable SQL integration database is required.');$app=$this->application(['database'=>['url'=>$url]]);$db=$app->container->get(\Elang\Database\Database::class)->connection();self::assertStringStartsWith('elang_test_',$db->getParams()['dbname']??'');\Elang\Realtime\Schema::up($db);$db->delete('elang_events',['channel'=>'test:ordered']);$db->delete('elang_event_channels',['channel'=>'test:ordered']);$events=$app->container->get(\Elang\Realtime\Events::class);$db->beginTransaction();$first=$events->publish('test:ordered','item.created',['source'=>'first']);
        $process=proc_open([PHP_BINARY,__DIR__.'/fixtures/event-publisher.php',$this->temporary],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$deadline=microtime(true)+5;while(!file_exists($this->temporary.'/publisher-started')&&microtime(true)<$deadline) usleep(10000);self::assertFileExists($this->temporary.'/publisher-started');usleep(150000);self::assertTrue(proc_get_status($process)['running'],'The second publisher must wait for the first transaction.');$db->commit();$output=trim(stream_get_contents($pipes[1]));$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);self::assertSame(0,proc_close($process),$error);self::assertSame($first+1,(int)$output);$rows=$events->after('test:ordered');self::assertSame(['first','second'],array_column(array_column($rows,'data'),'source'));
    }
}
