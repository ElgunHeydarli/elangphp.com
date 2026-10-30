<?php
declare(strict_types=1);
// A CLI-only, disposable teaching example. Never boot a production application.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
for($root=__DIR__;dirname($root)!==$root;$root=dirname($root)){
    if(is_file($root.'/vendor/autoload.php')){require $root.'/vendor/autoload.php';break;}
}
if(!class_exists(\Doctrine\ORM\EntityManager::class))throw new LogicException('Install dependencies and doctrine/orm:^3.7 first.');
require __DIR__.'/Entities/Category.php';require __DIR__.'/Entities/Product.php';require __DIR__.'/ProductResource.php';
use Elang\{Application,Orm\Orm,Support\Collection,Testing\TestClient};
use Elang\Http\Client\{Client,Response as PartnerResponse};
use ElangExample\Catalog\{Category,Product,ProductResource};
use Doctrine\ORM\Tools\SchemaTool;
$temporary=sys_get_temp_dir().'/elang-learning-'.bin2hex(random_bytes(8));mkdir($temporary,0700,true);
try{
    $app=new Application($temporary,['environment'=>'testing','debug'=>false,'url'=>'http://localhost','database'=>['url'=>'sqlite:///:memory:'],'mail'=>['dsn'=>'null://null'],'redis'=>['url'=>''],'telemetry'=>['enabled'=>false],'operations'=>['enabled'=>false],'orm'=>['enabled'=>true,'paths'=>[__DIR__.'/Entities']]]);
    $browser=new TestClient($app);$em=$app->container->get(Orm::class)->manager();
    // SchemaTool is appropriate here only because this is a throwaway, in-memory DB.
    (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
    $category=new Category('Office');$em->persist(new Product('Keyboard',5,$category));$em->persist(new Product('Mouse',3,$category));$em->flush();$em->clear();
    $app->router->get('/api/products',function()use($em){
        $items=$em->createQuery('SELECT p,c FROM '.Product::class.' p JOIN p.category c ORDER BY p.id')->getResult();
        return ProductResource::collection($items);
    });
    $browser->http->when('https://partner.example.test/stock',new PartnerResponse(200,'{"quantity":12}'));
    $app->router->get('/api/partner-stock',fn(Client $http)=>['quantity'=>$http->get('https://partner.example.test/stock')->throw()->json('quantity')]);
    $response=$browser->get('/api/products')->assertOk()->assertJsonCount(2,'data')->assertJsonPath('data.0.title','Keyboard')->assertDontSee('internalNote')->assertDontSee('Not for API responses');
    $browser->get('/api/partner-stock')->assertOk()->assertJsonPath('quantity',12);$browser->http->assertSentCount(1);
    $browser->assertDatabaseHas('example_products',['title'=>'Keyboard','quantity'=>5]);
    $total=(new Collection($response->json('data')))->sum('quantity');if($total!==8)throw new LogicException('Unexpected collection total.');
    echo json_encode(['items'=>2,'total_quantity'=>$total,'partner_quantity'=>12,'checks'=>'passed'],JSON_THROW_ON_ERROR)."\n";
}finally{
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $entry){$entry->isDir()&&!$entry->isLink()?rmdir($entry->getPathname()):unlink($entry->getPathname());}rmdir($temporary);
}
