<?php
declare(strict_types=1);
namespace Tests;
use Elang\{Database\Database,Orm\Orm,Orm\Provider};
use Tests\OrmFixtures\{Author,Book};
#[\PHPUnit\Framework\Attributes\Group('database-integration')]
final class OrmPortableTest extends TestCase
{
    public function testOrmMappingsAndSoftDeletionOnAnExplicitlyDisposableDatabase():void
    {
        $url=getenv('ELANG_TEST_DATABASE_URL');if(!$url)self::markTestSkipped('Set ELANG_TEST_DATABASE_URL to a disposable elang_test_* database.');
        $app=$this->application(['database'=>['url'=>$url],'orm'=>['paths'=>[__DIR__.'/OrmFixtures']]]);$connection=$app->container->get(Database::class)->connection();self::assertStringStartsWith('elang_test_',$connection->getParams()['dbname']??'','Use a disposable integration database.');$app->provider(new Provider());$orm=$app->container->get(Orm::class);$manager=$orm->manager();$schema=$connection->createSchemaManager();
        foreach(['orm_books','orm_authors'] as $table)if($schema->tablesExist([$table]))$schema->dropTable($table);
        try{
            (new \Doctrine\ORM\Tools\SchemaTool($manager))->createSchema($manager->getMetadataFactory()->getAllMetadata());
            $book=new Book('Portable',new Author('Əli'));$manager->persist($book);$manager->flush();$id=$book->id;$manager->clear();$book=$manager->find(Book::class,$id);self::assertSame('Əli',$book->author->name);self::assertNotNull($book->createdAt());
            $orm->softDelete($book);$manager->flush();$manager->clear();self::assertNull($manager->find(Book::class,$id));$book=$orm->withDeleted(fn($em)=>$em->find(Book::class,$id));$orm->restore($book);$manager->flush();$manager->clear();self::assertNotNull($manager->find(Book::class,$id));
        }finally{$manager->clear();foreach(['orm_books','orm_authors'] as $table)if($schema->tablesExist([$table]))$schema->dropTable($table);}
    }
}
