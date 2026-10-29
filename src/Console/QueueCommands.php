<?php
declare(strict_types=1);
namespace Elang\Console;
use Elang\{Application,Queue\Queue,Queue\Schedule};
use Symfony\Component\Console\{Application as Console,Command\Command,Input\InputArgument,Input\InputOption,Helper\Table};
final class QueueCommands
{
    public static function register(Console $console,Application $app):void
    {
        $console->add((new Command('queue:setup'))->setDescription('Create the database queue, event, and idempotency tables.')->addOption('force',null,InputOption::VALUE_NONE,'Allow production schema changes')->setCode(function($input,$output)use($app):int {
            if($app->config->get('environment')==='production'&&!$input->getOption('force')) throw new \RuntimeException('Review the production schema change, then use --force.');
            $database=$app->container->get(\Elang\Database\Database::class)->connection();\Elang\Queue\Schema::up($database);\Elang\Realtime\Schema::up($database);\Elang\Http\Idempotency\Schema::up($database);$output->writeln('<info>Runtime tables are ready.</info>');return 0;
        }));
        $console->add((new Command('queue:work'))->setDescription('Process registered JSON jobs; use a process supervisor in production.')->addOption('once',null,InputOption::VALUE_NONE,'Process at most one job')->addOption('stop-when-empty',null,InputOption::VALUE_NONE,'Exit when no job is ready')->addOption('max-jobs',null,InputOption::VALUE_REQUIRED,'Recycle the worker after this many jobs','1000')->setCode(function($input,$output)use($app):int {
            $max=filter_var($input->getOption('max-jobs'),FILTER_VALIDATE_INT);if(!$max||$max<1||$max>100000) throw new \InvalidArgumentException('max-jobs must be 1–100000.');
            $running=true;if(function_exists('pcntl_async_signals')&&function_exists('pcntl_signal')) { pcntl_async_signals(true);foreach([SIGTERM,SIGINT] as $signal) pcntl_signal($signal,function()use(&$running):void { $running=false; }); }
            $queue=$app->container->get(Queue::class);$processed=0;$workerId=bin2hex(random_bytes(16));$ops=$app->container->get(\Elang\Observability\Operations::class);
            while($running&&$processed<$max) { $ops->heartbeat($workerId,'queue',$processed);$worked=$queue->workOne();if($worked) $processed++;if($input->getOption('once')||!$worked&&$input->getOption('stop-when-empty')) break;if(!$worked) $queue->wait(); }
            $output->writeln('Processed '.$processed.' job attempt(s).');return 0;
        }));
        $console->add((new Command('queue:failed'))->setDescription('List failed job metadata without payloads.')->setCode(function($input,$output)use($app):int { (new Table($output))->setHeaders(['ID','Job','Attempts','Error class','Created'])->setRows(array_map('array_values',$app->container->get(Queue::class)->failures()))->render();return 0; }));
        $console->add((new Command('queue:retry'))->setDescription('Retry one reviewed failed job.')->addArgument('id',InputArgument::REQUIRED)->setCode(function($input,$output)use($app):int { $id=$input->getArgument('id');if(!preg_match('/^[a-f0-9]{32}$/D',$id)) throw new \InvalidArgumentException('Use a listed failed job ID.');$done=$app->container->get(Queue::class)->retry($id);$output->writeln($done?'Job queued again.':'Failed job not found.');return $done?0:1; }));
        $console->add((new Command('schedule:run'))->setDescription('Enqueue due UTC tasks once per minute across workers.')->setCode(function($input,$output)use($app):int { $output->writeln($app->container->get(Schedule::class)->runDue().' scheduled job(s) queued.');return 0; }));
        $console->add((new Command('realtime:serve'))->setDescription('Run authenticated WebSockets; install elang/realtime first.')->addOption('host',null,InputOption::VALUE_REQUIRED,'Listen IP','127.0.0.1')->addOption('port',null,InputOption::VALUE_REQUIRED,'Listen port','8081')->addOption('workers',null,InputOption::VALUE_REQUIRED,'Worker processes','1')->setCode(function($input)use($app):int {
            $port=filter_var($input->getOption('port'),FILTER_VALIDATE_INT);$workers=filter_var($input->getOption('workers'),FILTER_VALIDATE_INT);if($port===false||$workers===false) throw new \InvalidArgumentException('Port and workers must be integers.');$app->container->get(\Elang\Realtime\SocketServer::class)->run($input->getOption('host'),$port,$workers);return 0;
        }));
        $console->add((new Command('outbox:work'))->setDescription('Continuously relay committed SQL notifications to Redis.')->addOption('once',null,InputOption::VALUE_NONE,'Dispatch one batch')->setCode(function($input,$output)use($app):int {
            $running=true;if(function_exists('pcntl_async_signals')&&function_exists('pcntl_signal')) { pcntl_async_signals(true);foreach([SIGTERM,SIGINT] as $signal) pcntl_signal($signal,function()use(&$running):void { $running=false; }); }
            $relay=$app->container->get(\Elang\Outbox\Outbox::class);$id=bin2hex(random_bytes(16));$total=0;do { $total+=$relay->dispatch();$app->container->get(\Elang\Observability\Operations::class)->heartbeat($id,'outbox',$total);if(!$input->getOption('once')) usleep(250000); }while($running&&!$input->getOption('once'));$output->writeln($total.' notification(s) relayed.');return 0;
        }));
        $console->add((new Command('platform:setup'))->setDescription('Create reviewed platform tables for teams, files, notifications, security, and operations.')->addOption('force',null,InputOption::VALUE_NONE,'Allow production schema changes')->setCode(function($input,$output)use($app):int {
            if($app->config->get('environment')==='production'&&!$input->getOption('force')) throw new \RuntimeException('Review schema changes, then use --force.');$db=$app->container->get(\Elang\Database\Database::class)->connection();foreach([\Elang\Auth\Schema::class,\Elang\Queue\Schema::class,\Elang\Realtime\Schema::class,\Elang\Http\Idempotency\Schema::class,\Elang\Tenancy\Schema::class,\Elang\Storage\Schema::class,\Elang\Notification\Schema::class,\Elang\Observability\Schema::class] as $schema) $schema::up($db);$output->writeln('Platform tables are ready.');return 0;
        }));
        $console->add((new Command('operations:prune'))->setDescription('Prune old aggregate metrics, worker metadata, and published outbox rows.')->setCode(function($input,$output)use($app):int { $app->container->get(\Elang\Observability\Operations::class)->prune();$output->writeln('Expired operations metadata pruned.');return 0; }));
        $console->add((new Command('outbox:dispatch'))->setDescription('Relay committed SQL notifications to Redis Streams.')->setCode(function($input,$output)use($app):int { $output->writeln($app->container->get(\Elang\Outbox\Outbox::class)->dispatch().' notification(s) relayed.');return 0; }));
        $console->add((new Command('realtime:prune'))->setDescription('Remove event history older than 24 hours.')->setCode(function($input,$output)use($app):int { $output->writeln($app->container->get(\Elang\Realtime\Events::class)->prune(time()-86400).' expired event(s) removed.');return 0; }));
    }
}
