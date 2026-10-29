<?php
declare(strict_types=1);
namespace Tests;
use Symfony\Component\HttpFoundation\Request;
final class PackageRepositoryTest extends TestCase
{
    public function testComposerDistributionsAreCompleteAndExcludeRuntimeSecrets(): void
    {
        $output = $this->temporary.'/storage/distribution';
        $process = proc_open([PHP_BINARY, dirname(__DIR__).'/bin/build-packages', '--output='.$output, '--base-url=file://'.$output], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fclose($pipes[0]); $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stdout.$stderr);
        $metadata = json_decode(file_get_contents($output.'/packages.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['elang/framework','elang/elang','elang/task-board','elang/installer'], array_keys($metadata['packages']));
        foreach ($metadata['packages'] as $name => $versions) {
            $package = $versions['0.1.0-dev']; $file = parse_url($package['dist']['url'], PHP_URL_PATH);
            self::assertSame($package['dist']['shasum'], sha1_file($file));
            self::assertMatchesRegularExpression('/-[a-f0-9]{40}\.zip$/', $file);
            $archive = new \ZipArchive(); self::assertTrue($archive->open($file));
            self::assertFalse($archive->getFromName('package/.env'), $name);
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $path = $archive->getNameIndex($index); self::assertStringNotContainsString('/vendor/', $path); self::assertStringNotContainsString('/storage/', $path); self::assertStringNotContainsString('.sqlite', $path);
            }
            $composer = json_decode($archive->getFromName('package/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame($name, $composer['name']);
            if ($name === 'elang/framework') { self::assertArrayNotHasKey('App\\', $composer['autoload']['psr-4']); self::assertArrayNotHasKey('require-dev', $composer); }
            if (in_array($name, ['elang/elang', 'elang/task-board'], true)) {
                self::assertStringContainsString('APP_KEY=', $archive->getFromName('package/.env.example'));
                self::assertSame(['@php elang key:generate', '@php elang doctor'], $composer['scripts']['post-create-project-cmd']);
            }
            $archive->close();
        }
        $app = $this->application();
        $app->router->get('/packages', [\App\Controllers\PackageController::class, 'index']);
        $app->router->get('/packages/dist/{file}', [\App\Controllers\PackageController::class, 'dist']);
        self::assertSame(200, $app->handle(Request::create('/packages'))->getStatusCode());
        self::assertSame('noindex', $app->handle(Request::create('/packages'))->headers->get('X-Robots-Tag'));
        self::assertSame(404, $app->handle(Request::create('/packages/dist/.env'))->getStatusCode());
    }
}
