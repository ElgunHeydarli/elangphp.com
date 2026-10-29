<?php
declare(strict_types=1);
namespace Elang\Debug\Database;
use Doctrine\DBAL\Driver\{Connection,Result,Statement,Middleware\AbstractConnectionMiddleware};
use Elang\Debug\Collector;
final class TraceConnection extends AbstractConnectionMiddleware
{
    public function __construct(Connection $connection,private Collector $collector) { parent::__construct($connection); }
    public function prepare(string $sql): Statement { return new TraceStatement(parent::prepare($sql),$this->collector,$sql); }
    public function query(string $sql): Result { $start=microtime(true);try{return parent::query($sql);}finally{$this->collector->query($sql,(microtime(true)-$start)*1000);} }
    public function exec(string $sql): int|string { $start=microtime(true);try{return parent::exec($sql);}finally{$this->collector->query($sql,(microtime(true)-$start)*1000);} }
}
