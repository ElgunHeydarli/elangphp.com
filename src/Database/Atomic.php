<?php
declare(strict_types=1);
namespace Elang\Database;
use Doctrine\DBAL\{Connection,Platforms\AbstractMySQLPlatform,Platforms\SQLitePlatform};
final class Atomic
{
    public static function insertIfMissing(Connection $connection,string $table,array $data):void
    {
        foreach(array_merge([$table],array_keys($data)) as $identifier) if(!preg_match('/^[a-z_][a-z0-9_]*$/D',$identifier)) throw new \InvalidArgumentException('Invalid SQL identifier.');
        $platform=$connection->getDatabasePlatform();$quote=fn(string $id)=>$connection->quoteIdentifier($id);
        $prefix=$platform instanceof AbstractMySQLPlatform?'INSERT IGNORE':($platform instanceof SQLitePlatform?'INSERT OR IGNORE':'INSERT');
        $sql=$prefix.' INTO '.$quote($table).' ('.implode(',',array_map($quote,array_keys($data))).') VALUES ('.implode(',',array_fill(0,count($data),'?')).')';
        if(!$platform instanceof AbstractMySQLPlatform&&!$platform instanceof SQLitePlatform) $sql.=' ON CONFLICT DO NOTHING';$connection->executeStatement($sql,array_values($data));
    }
    /** Locking reads use the latest committed row under MySQL REPEATABLE READ, rather than an earlier consistent-read snapshot. */
    public static function currentRow(Connection $connection,string $sql,array $parameters=[]):array|false
    {
        if(!$connection->getDatabasePlatform() instanceof SQLitePlatform) $sql.=' FOR UPDATE';return $connection->fetchAssociative($sql,$parameters);
    }
    public static function lockRow(Connection $connection,string $table,string $column,string|int $value):void
    {
        foreach([$table,$column] as $identifier) if(!preg_match('/^[a-z_][a-z0-9_]*$/D',$identifier)) throw new \InvalidArgumentException('Invalid SQL identifier.');
        $table=$connection->quoteIdentifier($table);$column=$connection->quoteIdentifier($column);
        if($connection->getDatabasePlatform() instanceof SQLitePlatform) $connection->executeStatement('UPDATE '.$table.' SET '.$column.' = '.$column.' WHERE '.$column.' = ?',[$value]);
        else $connection->fetchOne('SELECT '.$column.' FROM '.$table.' WHERE '.$column.' = ? FOR UPDATE',[$value]);
    }
}
