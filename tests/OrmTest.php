<?php
declare(strict_types=1);
namespace Tests;
use Doctrine\ORM\{EntityManagerInterface,Tools\SchemaTool,OptimisticLockException};
use Elang\{Application,Database\Database,Orm\Orm,Orm\Provider};
use Tests\OrmFixtures\{Author,Book};
use Symfony\Component\Clock\MockClock;
final class OrmTest extends TestCase
{
    private function setupOrm():array
    {
        $app=$this->application(['orm'=>['paths'=>[__DIR__.'/OrmFixtures']]]);$clock=new MockClock('2026-10-30T08:00:00+00:00');$app->container->instance(\Psr\Clock\ClockInterface::class,$clock);$app->provider(new Provider());$orm=$app->container->get(Orm::class);$em=$orm->manager();(new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());return [$app,$orm,$clock];
    }
    public function testMappingsRelationshipsAndTheCoreConnectionAreShared():void
    {
        [$app,$orm]=$this->setupOrm();$em=$orm->manager();self::assertSame($em,$app->container->get(EntityManagerInterface::class));self::assertSame($app->container->get(Database::class)->connection(),$em->getConnection());
        $book=new Book('First',new Author('Writer'));$em->persist($book);$em->flush();$id=$book->id;$em->clear();$loaded=$em->find(Book::class,$id);self::assertSame('Writer',$loaded->author->name);self::assertSame('First',$loaded->title);self::assertNotNull($loaded->createdAt());
    }
    public function testTimestampsFollowTheInjectedClockAndSoftDeleteCanBeRestored():void
    {
        [$app,$orm,$clock]=$this->setupOrm();$em=$orm->manager();$book=new Book('Before',new Author('Writer'));$em->persist($book);$em->flush();self::assertSame('08:00:00',$book->createdAt()->format('H:i:s'));
        $clock->sleep(60);$book->title='After';$em->flush();$id=$book->id;$em->clear();$book=$em->find(Book::class,$id);self::assertSame('08:01:00',$book->updatedAt()->format('H:i:s'));
        $orm->softDelete($book);$em->flush();$em->clear();self::assertNull($em->find(Book::class,$id));$deleted=$orm->withDeleted(fn($em)=>$em->find(Book::class,$id));self::assertNotNull($deleted);self::assertNotNull($deleted->deletedAt());
        $orm->restore($deleted);$em->flush();$em->clear();self::assertNotNull($em->find(Book::class,$id));self::assertTrue($em->getFilters()->isEnabled('elang_soft_delete'));
    }
    public function testOrmAndCoreDatabaseWritesRollBackTogetherAndManagerCanReset():void
    {
        [$app,$orm]=$this->setupOrm();$db=$app->container->get(Database::class);$db->connection()->executeStatement('CREATE TABLE orm_pending (id INTEGER PRIMARY KEY,body VARCHAR(50))');$previous=$orm->manager();
        try{$orm->transaction(function($em)use($db){$em->persist(new Author('Rolled back'));$em->flush();$db->connection()->insert('orm_pending',['id'=>1,'body'=>'also rollback']);throw new \RuntimeException('rollback');});self::fail('Expected rollback');}catch(\RuntimeException $error){self::assertSame('rollback',$error->getMessage());}
        self::assertSame(0,(int)$db->connection()->fetchOne('SELECT COUNT(*) FROM orm_authors'));self::assertSame(0,(int)$db->connection()->fetchOne('SELECT COUNT(*) FROM orm_pending'));self::assertFalse($previous->isOpen());
        $orm->reset();self::assertNotSame($previous,$app->container->get(EntityManagerInterface::class));self::assertTrue($orm->manager()->isOpen());
    }
    public function testOptimisticLockRejectsAStaleEntity():void
    {
        [$app,$orm]=$this->setupOrm();$em=$orm->manager();$book=new Book('First',new Author('Writer'));$em->persist($book);$em->flush();$app->container->get(Database::class)->connection()->update('orm_books',['version'=>9],['id'=>$book->id]);$book->title='Stale';$this->expectException(OptimisticLockException::class);$em->flush();
    }
    public function testSqlCommandDoesNotChangeTheDatabase():void
    {
        $app=$this->application(['orm'=>['paths'=>[__DIR__.'/OrmFixtures']]]);$app->provider(new Provider());$command=(new \Elang\Console\Kernel($app))->console()->find('orm:schema:sql');$tester=new \Symfony\Component\Console\Tester\CommandTester($command);self::assertSame(0,$tester->execute([]));self::assertStringContainsString('CREATE TABLE',$tester->getDisplay());self::assertFalse($app->container->get(Database::class)->connection()->createSchemaManager()->tablesExist(['orm_books']));
    }
    public function testEntityGeneratorPreservesExistingFiles():void
    {
        $app=$this->application();$app->provider(new Provider());$tester=new \Symfony\Component\Console\Tester\CommandTester((new \Elang\Console\Kernel($app))->console()->find('make:entity'));self::assertSame(0,$tester->execute(['name'=>'Product']));$file=$this->temporary.'/app/Entities/Product.php';$source=file_get_contents($file);self::assertStringContainsString("name: 'products'",$source);
        try{$tester->execute(['name'=>'Product']);self::fail('Overwritten');}catch(\RuntimeException){}self::assertSame($source,file_get_contents($file));
    }
    public function testFrameworkCommandsCannotBeSilentlyOverridden():void
    {
        $app=$this->application();$app->command(new \Symfony\Component\Console\Command\Command('doctor'));$this->expectException(\LogicException::class);(new \Elang\Console\Kernel($app))->console();
    }
    public function testMetadataClearKeepsRowsAndProtectsAnActiveTransaction():void
    {
        [$app,$orm]=$this->setupOrm();$manager=$orm->manager();$manager->persist(new Author('Preserved'));$manager->flush();$connection=$manager->getConnection();$connection->beginTransaction();
        try{$orm->clearMetadataCache();self::fail('An active transaction was discarded.');}catch(\LogicException){}finally{$connection->rollBack();}
        self::assertTrue($manager->isOpen());$orm->clearMetadataCache();self::assertFalse($manager->isOpen());self::assertNotSame($manager,$orm->manager());self::assertCount(1,$orm->manager()->getRepository(Author::class)->findAll());
    }
}
