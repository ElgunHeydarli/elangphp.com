<?php
declare(strict_types=1);
namespace Tests;
use Elang\Data\{Model,Models,Factory};
use Elang\Database\Database;
final class ArticleModel extends Model
{
    protected const TABLE='articles';protected const FILLABLE=['title','owner_id','published'];protected const HIDDEN=['private_note'];protected const CASTS=['published'=>'bool','owner_id'=>'int'];
}
final class ModelsTest extends TestCase
{
    private function models(): Models
    {
        $database=new Database('sqlite:///:memory:');$database->connection()->executeStatement('CREATE TABLE articles (id INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT,owner_id INTEGER,published INTEGER,private_note TEXT)');return new Models($database);
    }
    public function testCrudPaginationAndScopes(): void
    {
        $models=$this->models();$repository=$models->of(ArticleModel::class);$factory=new Factory($repository,fn($i)=>['title'=>'Post '.$i,'owner_id'=>1,'published'=>1]);$factory->create(12);
        $other=$repository->create(['title'=>'Private','owner_id'=>2,'published'=>0]);$page=$repository->where('owner_id',1)->orderBy('id','DESC')->paginate(5,2);
        self::assertSame(12,$page->total);self::assertCount(5,$page);self::assertSame(3,$page->lastPage);self::assertTrue($page->items[0]->published);
        self::assertNull($repository->where('owner_id',1)->update($other->id,['title'=>'Stolen']));self::assertFalse($repository->where('owner_id',1)->delete($other->id));self::assertSame('Private',$repository->find($other->id)->title);
        $updated=$repository->where('owner_id',2)->update($other->id,['title'=>'Updated']);self::assertSame('Updated',$updated->title);self::assertArrayNotHasKey('private_note',$updated->toArray());
    }
    public function testMassAssignmentAndSqlIdentifiersAreProtected(): void
    {
        $repository=$this->models()->of(ArticleModel::class);$this->expectException(\InvalidArgumentException::class);$repository->create(['title'=>'Hello','is_admin'=>true]);
    }
    public function testCreateMustMatchScope(): void
    {
        $repository=$this->models()->of(ArticleModel::class);$this->expectException(\InvalidArgumentException::class);$repository->where('owner_id',1)->create(['title'=>'Wrong owner','owner_id'=>2]);
    }
    public function testUnsafeIdentifierRejected(): void { $this->expectException(\InvalidArgumentException::class);$this->models()->of(ArticleModel::class)->orderBy('id; DROP TABLE articles'); }
}
