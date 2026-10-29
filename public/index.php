<?php
declare(strict_types=1);
$base = dirname(__DIR__);
if (!is_file($base.'/vendor/autoload.php')) $base .= '/private/elang';
require $base.'/vendor/autoload.php';
/** @var Elang\Application $app */
$app = require $base.'/bootstrap/app.php';
$app->run();
