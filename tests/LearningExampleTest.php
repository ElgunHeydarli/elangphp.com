<?php
declare(strict_types=1);
namespace Tests;
use Symfony\Component\Process\Process;
final class LearningExampleTest extends TestCase
{
    public function testDocumentedCatalogueRunsWithoutAnApplicationDatabaseOrPartnerNetwork():void
    {
        $process=new Process([PHP_BINARY,dirname(__DIR__).'/examples/orm-catalog/run.php']);$process->setTimeout(30);$process->mustRun();
        self::assertSame(['items'=>2,'total_quantity'=>8,'partner_quantity'=>12,'checks'=>'passed'],json_decode(trim($process->getOutput()),true));
    }
}
