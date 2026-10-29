<?php
declare(strict_types=1);
namespace Elang\Database;
use Doctrine\DBAL\{Connection, DriverManager};
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Query\QueryBuilder;
final class Database
{
    private ?Connection $connection = null;
    public function __construct(private string $dsn, private ?\Elang\Debug\Collector $collector = null) {}
    public function connection(): Connection
    {
        if ($this->connection) return $this->connection;
        $config = new \Doctrine\DBAL\Configuration();
        if ($this->collector?->enabled()) $config->setMiddlewares([new \Elang\Debug\Database\TraceMiddleware($this->collector)]);
        $this->connection = DriverManager::getConnection((new DsnParser(['sqlite'=>'pdo_sqlite','mysql'=>'pdo_mysql','postgres'=>'pdo_pgsql','postgresql'=>'pdo_pgsql']))->parse($this->dsn), $config);
        if ($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform) { $this->connection->executeStatement('PRAGMA foreign_keys = ON'); $this->connection->executeStatement('PRAGMA busy_timeout = 5000'); }
        return $this->connection;
    }
    public function table(string $table): QueryBuilder
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $table)) throw new \InvalidArgumentException('Invalid table identifier.');
        return $this->connection()->createQueryBuilder()->select('*')->from($this->connection()->quoteIdentifier($table));
    }
    public function transaction(callable $callback): mixed { return $this->connection()->transactional(fn (Connection $connection) => $callback($connection)); }
}
