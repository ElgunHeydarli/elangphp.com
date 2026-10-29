<?php
declare(strict_types=1);
use Elang\Support\Config;
return [
    'name' => 'Elang', 'url' => Config::env('APP_URL', 'http://localhost:8000'),
    'environment' => Config::env('APP_ENV', 'production'),
    'debug' => filter_var(Config::env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'key' => Config::secret('APP_KEY', ''),
    'database' => ['url' => Config::env('DATABASE_URL', 'sqlite:///'.dirname(__DIR__).'/database/database.sqlite')],
    'mail' => ['dsn' => Config::secret('MAILER_DSN', 'null://null'), 'from' => Config::env('MAIL_FROM','noreply@elangphp.com')],
    'task_board' => ['path' => '/demo/task-board'],
    'auth' => ['registration' => false,'home'=>'/admin'],
    'rate_limit' => 120,
    'max_body_bytes' => 2097152,
];
