<?php
declare(strict_types=1);
use Elang\Application;
$loader = require dirname(__DIR__).'/vendor/autoload.php';
$loader->addPsr4('App\\', dirname(__DIR__).'/app');
$app = Application::create(dirname(__DIR__));
$app->provider(new App\Providers\SiteProvider());
$router = $app->router;
require dirname(__DIR__).'/routes/web.php';
return $app;
