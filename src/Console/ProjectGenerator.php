<?php
declare(strict_types=1);
namespace Elang\Console;
final class ProjectGenerator
{
    private function stageFramework(string $source, string $destination): void
    {
        mkdir($destination, 0770, true);
        $paths = ['composer.json', 'LICENSE', 'README.md', 'bin/elang', 'public/.htaccess', 'public/router.php'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source.'/src', \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) if ($file->isFile() && $file->getExtension() === 'php') $paths[] = substr($file->getPathname(), strlen($source) + 1);
        foreach ($paths as $relative) {
            if (!is_file($source.'/'.$relative)) continue;
            $target = $destination.'/'.$relative;
            if (!is_dir(dirname($target))) mkdir(dirname($target), 0770, true);
            copy($source.'/'.$relative, $target);
        }
    }
    public function generate(string $directory, ?string $frameworkPath = null): void
    {
        if (is_dir($directory) && count(scandir($directory)) > 2) throw new \RuntimeException('Destination must be empty.');
        if (!is_dir($directory) && !mkdir($directory, 0770, true)) throw new \RuntimeException('Could not create destination.');
        $composer = ['name' => 'app/elang-project', 'type' => 'project', 'license' => 'MIT', 'require' => ['php' => '^8.3', 'elang/framework' => 'dev-main'], 'autoload' => ['psr-4' => ['App\\' => 'app/']], 'repositories' => [$frameworkPath ? ['type' => 'path', 'url' => './.elang/framework', 'options' => ['symlink' => false, 'versions' => ['elang/framework' => 'dev-main']]] : ['type' => 'vcs', 'url' => 'https://github.com/ElgunHeydarli/elangphp.com']], 'minimum-stability' => 'dev', 'prefer-stable' => true, 'config' => ['allow-plugins' => false]];
        if ($frameworkPath) $this->stageFramework($frameworkPath, $directory.'/.elang/framework');
        $files = [
            'composer.json' => json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
            'bootstrap/app.php' => "<?php\ndeclare(strict_types=1);\n\$app = Elang\\Application::create(dirname(__DIR__));\n\$router = \$app->router;\nrequire dirname(__DIR__).'/routes/web.php';\nreturn \$app;\n",
            'routes/web.php' => "<?php\ndeclare(strict_types=1);\nuse Elang\\Http\\Response;\n\$router->get('/', fn () => Response::html('<!doctype html><html lang=\"en\"><title>Welcome to Elang</title><h1>Your next idea starts here.</h1><p>Built with Elang.</p></html>'), 'home');\n\$router->get('/api/hello/{name}', fn (string \$name) => ['message' => 'Hello, '.\$name.'!'], 'hello');\n",
            'public/index.php' => "<?php\ndeclare(strict_types=1);\nrequire dirname(__DIR__).'/vendor/autoload.php';\n\$app = require dirname(__DIR__).'/bootstrap/app.php';\n\$app->run();\n",
            'elang' => "#!/usr/bin/env php\n<?php\ndeclare(strict_types=1);\nrequire __DIR__.'/vendor/autoload.php';\n\$app = require __DIR__.'/bootstrap/app.php';\nexit((new Elang\\Console\\Kernel(\$app))->run());\n",
            '.env.example' => "APP_ENV=local\nAPP_DEBUG=false\nAPP_URL=http://localhost:8000\nAPP_KEY=\n",
            '.gitignore' => "/vendor/\n/.env\n/storage/*\n/database/*.sqlite*\n",
            'README.md' => "# Your Elang application\n\nRun `composer install`, `php elang key:generate`, and `php elang serve`.\n\nPublic web root: `public/`. Documentation: https://elangphp.com/docs/v0.1/installation\n",
            'resources/views/.gitkeep' => '', 'database/migrations/.gitkeep' => '', 'app/Controllers/.gitkeep' => '', 'storage/.gitkeep' => '',
        ];
        foreach (['.htaccess', 'router.php'] as $file) $files['public/'.$file] = file_get_contents(dirname(__DIR__, 2).'/public/'.$file);
        foreach ($files as $name => $content) {
            $path = $directory.'/'.$name;
            if (!is_dir(dirname($path))) mkdir(dirname($path), 0770, true);
            file_put_contents($path, $content);
        }
        chmod($directory.'/elang', 0755);
    }
}
