<?php
declare(strict_types=1);
namespace Tests;
use Elang\Database\{Database, Migrator};
final class DatabaseTest extends TestCase
{
    public function testBoundValuesAreNotExecutedAsSql(): void
    {
        $db = new Database('sqlite:///:memory:'); $db->connection()->executeStatement('CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT)');
        $payload = "Robert'); DROP TABLE posts;--";
        $db->connection()->insert('posts', ['id' => 1, 'title' => $payload]);
        $row = $db->table('posts')->where('title = :title')->setParameter('title', $payload)->fetchAssociative();
        self::assertSame($payload, $row['title']); self::assertSame(1, (int) $db->connection()->fetchOne('SELECT COUNT(*) FROM posts'));
    }
    public function testInvalidTableNameIsRejected(): void { $this->expectException(\InvalidArgumentException::class); (new Database('sqlite:///:memory:'))->table('posts; DROP TABLE posts'); }
    public function testTransactionsRollbackOnFailure(): void
    {
        $db = new Database('sqlite:///:memory:'); $db->connection()->executeStatement('CREATE TABLE posts (id INTEGER PRIMARY KEY)');
        try { $db->transaction(function ($connection) { $connection->insert('posts', ['id' => 1]); throw new \RuntimeException('fail'); }); } catch (\RuntimeException) {}
        self::assertSame(0, (int) $db->connection()->fetchOne('SELECT COUNT(*) FROM posts'));
    }
    public function testMigrationsAreIdempotentAndRollbackLatestBatch(): void
    {
        $db = new Database('sqlite:///:memory:'); $directory = dirname(__DIR__).'/database/migrations';
        $migrator = new Migrator($db, $directory, $this->temporary.'/migrations.lock');
        self::assertCount(3, $migrator->migrate()); self::assertSame([], $migrator->migrate());
        self::assertSame(3, (int) $db->connection()->fetchOne('SELECT COUNT(*) FROM examples'));
        self::assertCount(3, $migrator->rollback()); self::assertFalse($db->connection()->createSchemaManager()->tablesExist(['examples'])); self::assertSame([], $migrator->rollback());
    }
    public function testFailedSqliteMigrationRollsBackSchema(): void
    {
        $db = new Database('sqlite:///:memory:');
        file_put_contents($this->temporary.'/001_failure.php', '<?php return new class implements \\Elang\\Database\\Migration { public function up(\\Doctrine\\DBAL\\Connection $database): void { $database->executeStatement("CREATE TABLE partial (id INTEGER)"); throw new \\RuntimeException("failure"); } public function down(\\Doctrine\\DBAL\\Connection $database): void {} };');
        $migrator = new Migrator($db, $this->temporary, $this->temporary.'/migrations.lock');
        try { $migrator->migrate(); self::fail('Expected a migration error.'); } catch (\RuntimeException $e) { self::assertSame('failure', $e->getMessage()); }
        self::assertFalse($db->connection()->createSchemaManager()->tablesExist(['partial'])); self::assertSame(['001_failure.php' => false], $migrator->status());
    }
}
