<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
$app=new Elang\Application($argv[1],['database'=>['url'=>getenv('ELANG_TEST_DATABASE_URL')],'paths'=>['storage'=>$argv[1].'/worker-storage']]);file_put_contents($argv[1].'/publisher-started','1');$sequence=$app->container->get(Elang\Realtime\Events::class)->publish('test:ordered','item.created',['source'=>'second']);echo $sequence."\n";
