<?php
declare(strict_types=1);
use Elang\Support\Config;
return [
    'name' => 'Elang', 'url' => Config::env('APP_URL', 'http://localhost:8000'),
    'environment' => Config::env('APP_ENV', 'production'),
    'debug' => filter_var(Config::env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'key' => Config::env('APP_KEY', ''),
    'database' => ['url' => Config::env('DATABASE_URL', 'sqlite:///'.dirname(__DIR__).'/database/database.sqlite')],
    'mail' => ['dsn' => Config::env('MAILER_DSN', 'null://null')],
    'rate_limit' => 120,
    'max_body_bytes' => 16384,
];
