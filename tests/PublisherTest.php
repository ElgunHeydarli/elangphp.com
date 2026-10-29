<?php
declare(strict_types=1);
namespace Tests;
final class PublisherTest extends TestCase
{
    private function fixture():array
    {
        $domain=$this->temporary.'/elangphp.com';$source=$domain.'/private/elang';$release=$domain.'/private/elang-review';
        foreach([$source.'/bin',$source.'/public',$source.'/storage',$release.'/vendor',$release.'/public',$release.'/storage',$domain.'/public_html'] as $directory) mkdir($directory,0770,true);
        copy(dirname(__DIR__).'/bin/publish-site',$source.'/bin/publish-site');file_put_contents($source.'/public/index.php','shared bootstrap');file_put_contents($release.'/vendor/autoload.php','<?php');file_put_contents($release.'/public/index.php','old release bootstrap');file_put_contents($domain.'/public_html/index.php','working site');symlink('elang',$domain.'/private/current');
        return [$domain,$source,$release];
    }
    private function publish(string $source):array
    {
        $process=new \Symfony\Component\Process\Process([PHP_BINARY,$source.'/bin/publish-site','--release=elang-review']);$process->run();return [$process->getExitCode(),$process->getOutput().$process->getErrorOutput()];
    }
    public function testUnconnectedRuntimeStorageIsRejectedBeforeChangingTheLiveSite():void
    {
        [$domain,$source]=$this->fixture();[$code,$output]=$this->publish($source);self::assertNotSame(0,$code);self::assertStringContainsString('shared private storage',$output);self::assertSame('elang',readlink($domain.'/private/current'));self::assertSame('working site',file_get_contents($domain.'/public_html/index.php'));
    }
    public function testReviewedReleaseUsesSharedRuntimeAndCurrentBootstrap():void
    {
        [$domain,$source,$release]=$this->fixture();rmdir($release.'/storage');symlink($source.'/storage',$release.'/storage');file_put_contents($release.'/public/router.php','private development router');[$code,$output]=$this->publish($source);self::assertSame(0,$code,$output);self::assertSame('elang-review',readlink($domain.'/private/current'));self::assertSame('shared bootstrap',file_get_contents($domain.'/public_html/index.php'));self::assertFileDoesNotExist($domain.'/public_html/router.php');
    }
}
