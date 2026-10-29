<?php
declare(strict_types=1);
namespace Tests;
final class GeneratorTest extends TestCase
{
    public function testGeneratedApplicationHasPrivateCodeAndPublicEntry(): void
    {
        (new \Elang\Console\ProjectGenerator())->generate($this->temporary.'/app', dirname(__DIR__));
        $composer = json_decode(file_get_contents($this->temporary.'/app/composer.json'), true);
        self::assertSame('dev-main', $composer['require']['elang/framework']); self::assertFalse($composer['repositories'][0]['options']['symlink']);
        self::assertFileExists($this->temporary.'/app/public/index.php'); self::assertFileExists($this->temporary.'/app/bootstrap/app.php'); self::assertFileExists($this->temporary.'/app/.env.example');
        self::assertFileDoesNotExist($this->temporary.'/app/.elang/framework/.env');
        self::assertDirectoryDoesNotExist($this->temporary.'/app/.elang/framework/vendor');
        self::assertDirectoryDoesNotExist($this->temporary.'/app/.elang/framework/storage');
    }
    public function testGeneratorRefusesNonemptyDestination(): void
    {
        file_put_contents($this->temporary.'/keep', 'important'); $this->expectException(\RuntimeException::class); (new \Elang\Console\ProjectGenerator())->generate($this->temporary);
    }
}
