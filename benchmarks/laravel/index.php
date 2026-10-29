<?php
declare(strict_types=1);
require __DIR__.'/vendor/autoload.php';
foreach (['bootstrap/cache', 'storage/framework/views', 'storage/logs'] as $directory) if (!is_dir(__DIR__.'/'.$directory)) mkdir(__DIR__.'/'.$directory, 0770, true);
$app = Illuminate\Foundation\Application::configure(basePath: __DIR__)
    ->withRouting(using: function (): void {
        Illuminate\Support\Facades\Route::get('/hello', fn () => response()->json(['message' => 'Hello, World!']));
    })
    ->withExceptions(function (Illuminate\Foundation\Configuration\Exceptions $exceptions): void {})
    ->create();
$request = Illuminate\Http\Request::capture();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);
$response->headers->set('X-Benchmark-Runtime', PHP_VERSION.';opcache_cli='.ini_get('opcache.enable_cli'));
$response->headers->set('X-Benchmark-Peak-Memory', (string) memory_get_peak_usage(true));
$response->send();
$kernel->terminate($request, $response);
