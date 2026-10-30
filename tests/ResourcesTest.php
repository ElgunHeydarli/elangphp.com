<?php
declare(strict_types=1);
namespace Tests;
use Elang\Http\Resource;
use Symfony\Component\HttpFoundation\Request;
final class PublicAccountResource extends Resource
{
    public function toArray(Request $request):array { return ['id'=>$this->resource['id'],'name'=>$this->resource['name']]; }
}
final class ResourcesTest extends TestCase
{
    public function testResourcesRunThroughTheRealKernelAndOmitPrivateFields():void
    {
        $app=$this->application();$app->router->get('/api/account',fn()=>new PublicAccountResource(['id'=>1,'name'=>'Public','password'=>'not-public']));
        $response=$app->handle(Request::create('/api/account'));self::assertSame(200,$response->getStatusCode());self::assertSame(['data'=>['id'=>1,'name'=>'Public']],json_decode($response->getContent(),true));self::assertTrue($response->headers->has('X-Request-Id'));
    }
    public function testPaginatedResourcesKeepMetadataButNeverSerializeRawModels():void
    {
        $app=$this->application();$page=new \Elang\Data\Paginator([['id'=>1,'name'=>'Public','private'=>'hidden']],31,10,2);
        $app->router->get('/api/accounts',fn()=>PublicAccountResource::collection($page));$body=json_decode($app->handle(Request::create('/api/accounts'))->getContent(),true);
        self::assertSame(31,$body['meta']['total']);self::assertSame(4,$body['meta']['last_page']);self::assertSame([['id'=>1,'name'=>'Public']],$body['data']);
    }
}
