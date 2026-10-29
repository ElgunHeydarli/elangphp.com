<?php
declare(strict_types=1);
namespace Elang\Contracts;
use Elang\Application;
use Elang\Container\Container;
interface ServiceProvider
{
    public function register(Container $container): void;
    public function boot(Application $app): void;
}
