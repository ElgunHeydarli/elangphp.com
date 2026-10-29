<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = new Elang\Application(__DIR__, ['debug' => false, 'environment' => 'benchmark', 'paths' => ['storage' => sys_get_temp_dir().'/elang-http-benchmark']]);
$app->router->get('/hello', fn () => Elang\Http\Response::json(['message' => 'Hello, World!']));
$response = $app->handle(Symfony\Component\HttpFoundation\Request::createFromGlobals());
$response->headers->set('X-Benchmark-Runtime', PHP_VERSION.';opcache_cli='.ini_get('opcache.enable_cli'));
$response->headers->set('X-Benchmark-Peak-Memory', (string) memory_get_peak_usage(true));
$response->send();
