<?php
declare(strict_types=1);
$app = Elang\Application::create(dirname(__DIR__));
$router = $app->router;
require dirname(__DIR__).'/routes/web.php';
return $app;
