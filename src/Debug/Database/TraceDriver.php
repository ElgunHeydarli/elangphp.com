<?php
declare(strict_types=1);
namespace Elang\Debug\Database;
use Doctrine\DBAL\Driver\{Connection,Middleware\AbstractDriverMiddleware};
use Elang\Debug\Collector;
final class TraceDriver extends AbstractDriverMiddleware
{
    public function __construct(\Doctrine\DBAL\Driver $driver,private Collector $collector) { parent::__construct($driver); }
    public function connect(#[\SensitiveParameter] array $params): Connection { return new TraceConnection(parent::connect($params),$this->collector); }
}
