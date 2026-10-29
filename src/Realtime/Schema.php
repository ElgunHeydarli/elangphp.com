<?php
declare(strict_types=1);
namespace Elang\Realtime;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $connection):void
    {
        $schema=$connection->createSchemaManager();
        if(!$schema->tablesExist(['elang_event_channels'])) {
            $channels=new Table('elang_event_channels');$channels->addColumn('channel','string',['length'=>160]);$channels->addColumn('last_sequence','bigint',['default'=>0]);$channels->setPrimaryKey(['channel']);$schema->createTable($channels);
        }
        if(!$schema->tablesExist(['elang_events'])) {
            $table=new Table('elang_events');$table->addColumn('id','bigint',['autoincrement'=>true]);$table->addColumn('channel','string',['length'=>160]);$table->addColumn('sequence','bigint');$table->addColumn('event','string',['length'=>64]);$table->addColumn('payload','text');$table->addColumn('created_at','bigint');$table->setPrimaryKey(['id']);$table->addUniqueIndex(['channel','sequence']);$table->addIndex(['created_at']);$schema->createTable($table);
        } else {
            $old=$schema->introspectTable('elang_events');
            if(!$old->hasColumn('sequence')) {
                $new=clone $old;$new->addColumn('sequence','bigint',['default'=>0]);$new->addUniqueIndex(['channel','sequence']);
                // Add the column first; existing rows must be backfilled before uniqueness.
                $withColumn=clone $old;$withColumn->addColumn('sequence','bigint',['default'=>0]);$schema->alterTable($schema->createComparator()->compareTables($old,$withColumn));
                $connection->executeStatement('UPDATE elang_events SET sequence = id');$current=$schema->introspectTable('elang_events');$target=clone $current;$target->addUniqueIndex(['channel','sequence']);$schema->alterTable($schema->createComparator()->compareTables($current,$target));
                foreach($connection->fetchAllAssociative('SELECT channel, MAX(sequence) AS last_sequence FROM elang_events GROUP BY channel') as $row) \Elang\Database\Atomic::insertIfMissing($connection,'elang_event_channels',$row);
            }
        }
        \Elang\Outbox\Schema::up($connection);
    }
}
