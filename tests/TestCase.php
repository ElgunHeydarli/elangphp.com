<?php
declare(strict_types=1);
namespace Tests;
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected string $temporary;
    protected function setUp(): void
    {
        $this->temporary = sys_get_temp_dir().'/elang-test-'.bin2hex(random_bytes(8));
        mkdir($this->temporary, 0770, true);
    }
    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temporary, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        rmdir($this->temporary);
    }
    protected function application(array $config = []): \Elang\Application
    {
        return new \Elang\Application($this->temporary, array_replace_recursive(['debug' => false, 'environment' => 'testing', 'database' => ['url' => 'sqlite:///:memory:'], 'mail'=>['dsn'=>'null://null','from'=>'noreply@example.test']], $config));
    }
}
