<?php
declare(strict_types=1);
namespace Elang\Debug\Database;
use Doctrine\DBAL\Driver;
use Elang\Debug\Collector;
final class TraceMiddleware implements \Doctrine\DBAL\Driver\Middleware
{
    public function __construct(private Collector $collector) {}
    public function wrap(Driver $driver): Driver { return new TraceDriver($driver,$this->collector); }
}
