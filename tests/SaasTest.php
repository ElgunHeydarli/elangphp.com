<?php
declare(strict_types=1);
namespace Tests;
final class SaasTest extends TestCase
{
    public function testReferenceApplicationEnforcesDomainBoundaries():void
    {
        $directory=$this->temporary.'/saas';(new \Elang\Console\ProjectGenerator())->generate($directory,null,'saas');file_put_contents($directory.'/.env',"APP_ENV=testing\nAPP_URL=http://localhost\nAPP_KEY=".base64_encode(random_bytes(32))."\n");$process=proc_open([PHP_BINARY,__DIR__.'/fixtures/saas-smoke.php',$directory],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$output=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);self::assertSame(0,proc_close($process),$output.$error);self::assertStringContainsString('revocation passed',$output);
    }
}
