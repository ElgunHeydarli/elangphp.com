<?php
declare(strict_types=1);
namespace Elang\Database;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Platforms\{SQLitePlatform, PostgreSQLPlatform};
final class Migrator
{
    public function __construct(private Database $database, private string $directory, private ?string $lockFile = null) {}
    private function synchronized(callable $action): array
    {
        $path = $this->lockFile ?? sys_get_temp_dir().'/elang-migrations-'.hash('sha256', $this->directory).'.lock';
        $handle = fopen($path, 'c');
        if (!$handle || !flock($handle, LOCK_EX)) throw new \RuntimeException('Cannot acquire migration lock.');
        try { return $action(); }
        finally { flock($handle, LOCK_UN); fclose($handle); }
    }
    private function change(callable $action): void
    {
        $connection = $this->database->connection();
        $platform = $connection->getDatabasePlatform();
        if ($platform instanceof SQLitePlatform || $platform instanceof PostgreSQLPlatform) $connection->transactional($action);
        else $action();
    }
    private function prepare(): void
    {
        $schema = $this->database->connection()->createSchemaManager();
        if (!$schema->tablesExist(['elang_migrations'])) {
            $table = new Table('elang_migrations');
            $table->addColumn('version', 'string', ['length' => 190]);
            $table->addColumn('batch', 'integer');
            $table->setPrimaryKey(['version']); $schema->createTable($table);
        }
    }
    private function readStatus(): array
    {
        $this->prepare();
        $applied = $this->database->connection()->fetchFirstColumn('SELECT version FROM elang_migrations');
        $status = [];
        foreach (glob($this->directory.'/*.php') ?: [] as $file) $status[basename($file)] = in_array(basename($file), $applied, true);
        ksort($status); return $status;
    }
    public function status(): array { return $this->synchronized(fn () => $this->readStatus()); }
    public function migrate(): array
    {
        return $this->synchronized(function (): array {
            $status = $this->readStatus(); $done = []; $connection = $this->database->connection();
            $batch = 1 + (int) $connection->fetchOne('SELECT MAX(batch) FROM elang_migrations');
            foreach ($status as $name => $applied) {
                if ($applied) continue;
                $migration = require $this->directory.'/'.$name;
                if (!$migration instanceof Migration) throw new \RuntimeException('Migration must implement '.Migration::class);
                $this->change(function () use ($migration, $connection, $name, $batch): void {
                    $migration->up($connection);
                    $connection->insert('elang_migrations', ['version' => $name, 'batch' => $batch]);
                });
                $done[] = $name;
            }
            return $done;
        });
    }
    public function rollback(): array
    {
        return $this->synchronized(function (): array {
            $this->prepare(); $done = []; $connection = $this->database->connection();
            $batch = $connection->fetchOne('SELECT MAX(batch) FROM elang_migrations');
            if (!$batch) return [];
            foreach ($connection->fetchFirstColumn('SELECT version FROM elang_migrations WHERE batch = ? ORDER BY version DESC', [$batch]) as $name) {
                $file = $this->directory.'/'.basename($name);
                if (!is_file($file)) throw new \RuntimeException('Missing migration: '.$name);
                $migration = require $file;
                if (!$migration instanceof Migration) throw new \RuntimeException('Invalid migration: '.$name);
                $this->change(function () use ($migration, $connection, $name): void {
                    $migration->down($connection); $connection->delete('elang_migrations', ['version' => $name]);
                });
                $done[] = $name;
            }
            return $done;
        });
    }
}
