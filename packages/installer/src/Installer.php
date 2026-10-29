<?php
declare(strict_types=1);
namespace Elang\Installer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputOption};
use Symfony\Component\Process\{Process, ExecutableFinder};
final class Installer
{
    public function run(): int
    {
        $version = \Composer\InstalledVersions::getPrettyVersion('elang/installer') ?? '0.1.0-dev';
        $console = new Application('Elang Installer', $version);
        $console->add((new Command('new'))->setDescription('Create an Elang PHP application and install its dependencies.')
            ->addArgument('directory', InputArgument::REQUIRED, 'A new or empty application directory')
            ->addOption('starter', null, InputOption::VALUE_REQUIRED, 'Application starter: auth, blog, dashboard, api, react, next, saas')
            ->addOption('example', null, InputOption::VALUE_REQUIRED, 'Optional complete example: task-board')
            ->addOption('repository', null, InputOption::VALUE_REQUIRED, 'Optional self-hosted Composer repository; defaults to Packagist')
            ->setCode(function ($input, $output) use ($version): int {
                $directory = $input->getArgument('directory');
                if ($directory === '' || str_starts_with($directory, '-') || preg_match('/[\x00-\x1f]/', $directory)) throw new \InvalidArgumentException('Choose a valid new project directory.');
                $example = $input->getOption('example');
                if ($example !== null && $example !== 'task-board') throw new \InvalidArgumentException('Available example: task-board.');
                $starter=$input->getOption('starter');
                if($starter!==null&&!in_array($starter,['auth','blog','dashboard','api','react','next','saas'],true)) throw new \InvalidArgumentException('Available starters: auth, blog, dashboard, api, react, next, saas.');
                if($starter!==null&&$example!==null) throw new \InvalidArgumentException('Choose a starter or an example.');
                $repository = $input->getOption('repository');
                if ($repository !== null && !str_starts_with($repository, 'https://') && !str_starts_with($repository, 'file://')) throw new \InvalidArgumentException('Use an HTTPS repository URL. A file:// repository is supported for local verification.');
                if ($repository !== null && str_starts_with($repository, 'file://')) $repository = json_encode(['type' => 'composer', 'url' => rtrim($repository, '/')], JSON_THROW_ON_ERROR);
                $composer = (new ExecutableFinder())->find('composer');
                if (!$composer) throw new \RuntimeException('Composer is not on PATH. Install Composer 2, reopen your terminal, and try again.');
                $package = $starter!==null?'elang/'.($starter==='auth'?'auth-starter':$starter):($example==='task-board'?'elang/task-board':'elang/elang');
                $output->writeln('<info>Creating your Elang application…</info>');
                $arguments = [$composer, 'create-project', '--prefer-dist', '--no-interaction'];
                if ($repository !== null) $arguments[] = '--repository='.$repository;
                $projectVersion = $repository !== null ? '0.1.0-dev' : (str_starts_with($version, 'dev-') ? 'dev-main' : $version);
                $process = new Process([...$arguments, $package, $directory, $projectVersion]);
                $process->setTimeout(null);
                $status = $process->run(fn ($type, $buffer) => $output->write($buffer));
                if ($status !== 0) return $status;
                $output->writeln('<info>Ready. Open the new directory and run php elang serve.</info>');
                return 0;
            }));
        return $console->run();
    }
}
