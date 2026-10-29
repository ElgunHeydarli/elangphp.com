<?php
declare(strict_types=1);
use App\Controllers\TaskBoardController;
use Elang\Http\Middleware\{SessionMiddleware, CsrfMiddleware, RateLimitMiddleware};
$router->group('', [RateLimitMiddleware::class, SessionMiddleware::class, CsrfMiddleware::class], function ($router) {
    $router->get('/', [TaskBoardController::class, 'index'], 'board');
    $router->post('/add', [TaskBoardController::class, 'store']);
    $router->post('/toggle/{id}', [TaskBoardController::class, 'toggle'])->where('id', '[a-f0-9]{16}');
    $router->post('/delete/{id}', [TaskBoardController::class, 'delete'])->where('id', '[a-f0-9]{16}');
});
