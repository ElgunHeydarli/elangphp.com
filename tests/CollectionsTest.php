<?php
declare(strict_types=1);
namespace Tests;
use Elang\Support\{Arr,Collection,LazyCollection};
final class CollectionsTest extends TestCase
{
    public function testTransformationsPreserveSourceKeysAndNullValues(): void
    {
        $original=new Collection(['a'=>['id'=>1,'profile'=>['name'=>'A'],'active'=>true,'amount'=>3],'b'=>['id'=>2,'profile'=>['name'=>null],'active'=>false,'amount'=>8]]);
        self::assertSame(['a'=>'A'],$original->where('active',true)->map(fn($row)=>$row['profile']['name'])->all());
        self::assertSame([1=>'A',2=>null],$original->pluck('profile.name','id')->all());
        self::assertTrue(Arr::has(['x'=>null],'x'));self::assertFalse(Arr::has(['x'=>null],'x.missing'));
        self::assertSame(11,$original->sum('amount'));self::assertSame([2,1],$original->sortBy('amount',true)->pluck('id')->all());self::assertCount(2,$original);
        self::assertCount(1,$original->groupBy(fn($row)=>'group')->get('group')->chunk(2));
        self::assertSame(['a','b'],$original->keys()->all());
    }
    public function testLazyPipelineDoesNotReadPastTheRequestedBoundary(): void
    {
        $read=0;$values=new LazyCollection(function()use(&$read){for($i=0;$i<1000000;$i++){++$read;yield $i;}});
        self::assertSame([0,4,8],$values->filter(fn($n)=>$n%2===0)->map(fn($n)=>$n*2)->take(3)->collect()->values()->all());self::assertSame(5,$read);
        $read=0;self::assertSame([],$values->take(0)->collect()->all());self::assertSame(0,$read);
        self::assertSame([[0,1],[2,3],[4]],$values->take(5)->chunk(2)->collect()->toArray());
    }
    public function testModelHiddenFieldsStayHiddenInNestedCollectionSerialization(): void
    {
        $model=new class(['id'=>1,'secret'=>'hidden']) extends \Elang\Data\Model { protected const HIDDEN=['secret']; };
        self::assertSame(['nested'=>[['id'=>1]]],(new Collection(['nested'=>new Collection([$model])]))->toArray());
    }
    public function testInvalidChunkSizeIsRejected():void { $this->expectException(\InvalidArgumentException::class);(new LazyCollection(fn()=>[1]))->chunk(0); }
}
