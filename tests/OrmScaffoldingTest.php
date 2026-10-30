<?php
declare(strict_types=1);
namespace Tests;
use Elang\Console\{ProjectGenerator,Kernel};
use Elang\Orm\{Orm,Provider};
use Symfony\Component\Console\Tester\CommandTester;
final class OrmScaffoldingTest extends TestCase
{
    public function testOptInGeneratorAddsTheDependencyAndPreservesKeys():void
    {
        $directory=$this->temporary.'/project';$generator=new ProjectGenerator();$generator->generate($directory,null,null,true);
        self::assertSame('^3.7',json_decode(file_get_contents($directory.'/composer.json'),true)['require']['doctrine/orm']);
        self::assertStringContainsString('ORM_ENABLED=true',file_get_contents($directory.'/.env.example'));
        self::assertDirectoryExists($directory.'/app/Entities');
        file_put_contents($directory.'/.env',"APP_KEY=unchanged-test-fixture\nMAIL_FROM=unchanged@example.test\nORM_ENABLED=false\n");
        $generator->enableOrm($directory);$generator->enableOrm($directory);$environment=file_get_contents($directory.'/.env');
        self::assertSame(1,substr_count($environment,'ORM_ENABLED=true'));self::assertStringContainsString('APP_KEY=unchanged-test-fixture',$environment);self::assertStringContainsString('MAIL_FROM=unchanged@example.test',$environment);
    }
    public function testDefaultGeneratorDoesNotRequireTheOrm():void
    {
        $directory=$this->temporary.'/plain';(new ProjectGenerator())->generate($directory);
        self::assertArrayNotHasKey('doctrine/orm',json_decode(file_get_contents($directory.'/composer.json'),true)['require']);
        self::assertStringContainsString('ORM_ENABLED=false',file_get_contents($directory.'/.env.example'));
    }
    public function testProviderCanBeEnabledByConfigurationAndRegisteredIdempotently():void
    {
        mkdir($this->temporary.'/app/Entities',0770,true);$app=$this->application(['orm'=>['enabled'=>true]]);$app->provider(new Provider());
        self::assertInstanceOf(Orm::class,$app->container->get(Orm::class));self::assertCount(5,$app->commands());self::assertTrue((new Kernel($app))->console()->has('make:entity'));
    }
    public function testCoreNewCommandAcceptsTheOptionalFlag():void
    {
        $directory=$this->temporary.'/command-app';$tester=new CommandTester((new Kernel($this->application()))->console()->find('new'));
        self::assertSame(0,$tester->execute(['directory'=>$directory,'--orm'=>true]));self::assertDirectoryExists($directory.'/app/Entities');
    }
}
