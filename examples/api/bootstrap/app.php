<?php
declare(strict_types=1);
$app=Elang\Application::create(dirname(__DIR__));
$app->middleware(Elang\Http\Middleware\Cors::class);
require __DIR__.'/messages.php';
$app->provider(new Elang\Auth\AuthProvider());
$router=$app->router;
require dirname(__DIR__).'/routes/web.php';
return $app;
