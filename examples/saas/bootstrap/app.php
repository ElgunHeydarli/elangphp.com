<?php
declare(strict_types=1);
$app=Elang\Application::create(dirname(__DIR__));
require __DIR__.'/messages.php';
$app->middleware(Elang\Http\Middleware\Cors::class);
$app->provider(new Elang\Auth\AuthProvider());
$app->provider(new Elang\Notification\Provider());
$app->provider(new Elang\Observability\Provider());
$router=$app->router;
require dirname(__DIR__).'/routes/web.php';
return $app;
