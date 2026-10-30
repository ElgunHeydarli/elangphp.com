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
    public function testTheDocumentedApplicationTestRunsWhenCopiedIntoAFile():void
    {
        $root=dirname(__DIR__);$markdown=file_get_contents($root.'/content/docs/v0.1/application-testing.md');
        self::assertSame(1,preg_match('/```php\n([\s\S]*?)```/',$markdown,$example));self::assertStringStartsWith('<?php',$example[1]);
        $file=$this->temporary.'/ApiTest.php';file_put_contents($file,$example[1]);
        $process=new Process([PHP_BINARY,$root.'/vendor/bin/phpunit','--no-configuration','--bootstrap',$root.'/vendor/autoload.php',$file],$this->temporary);$process->setTimeout(30);$process->mustRun();self::assertStringContainsString('OK (1 test',$process->getOutput());
    }
}
