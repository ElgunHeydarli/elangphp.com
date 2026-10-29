<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
$url=getenv('ELANG_TEST_DATABASE_URL');if(!$url) exit(2);
$app=new Elang\Application($argv[1],['database'=>['url'=>$url],'environment'=>'testing']);$db=$app->container->get(Elang\Database\Database::class)->connection();if(!str_starts_with($db->getParams()['dbname']??'','elang_test_')) exit(3);
$queue=$app->container->get(Elang\Queue\Queue::class);while($job=$queue->claim()) { echo $job['id']."\n";usleep(1000);if(!$queue->complete($job)) exit(4); }
