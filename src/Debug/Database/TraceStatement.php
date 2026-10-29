<?php
declare(strict_types=1);
namespace Elang\Debug\Database;
use Doctrine\DBAL\Driver\{Statement,Result,Middleware\AbstractStatementMiddleware};
use Elang\Debug\Collector;
final class TraceStatement extends AbstractStatementMiddleware
{
    public function __construct(Statement $statement,private Collector $collector,private string $sql) { parent::__construct($statement); }
    public function execute(): Result { $start=microtime(true);try{return parent::execute();}finally{$this->collector->query($this->sql,(microtime(true)-$start)*1000);} }
}
