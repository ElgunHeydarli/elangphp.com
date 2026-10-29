<?php
declare(strict_types=1);
namespace Elang\Console;
use Elang\Application;
use Elang\Database\Migrator;
use Symfony\Component\Console\Application as Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputOption, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
final class Kernel
{
    public function __construct(private Application $app) {}
    public function run(): int
    {
        $console = new Console('Elang', Application::VERSION);
        $console->add((new Command('doctor'))->setDescription('Check requirements, configuration, storage, and database connectivity.')->addOption('json', null, InputOption::VALUE_NONE, 'Return machine-readable results without secrets.')->setCode(function ($input, $output) {
            $checks = (new Doctor())->inspect($this->app);
            $healthy = !in_array('error', array_column($checks, 'status'), true);
            if ($input->getOption('json')) $output->writeln(json_encode(['healthy' => $healthy, 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            else {
                (new Table($output))->setHeaders(['Check', 'Status', 'Detail'])->setRows(array_map(fn ($check) => [$check['name'], strtoupper($check['status']), $check['detail']], $checks))->render();
                foreach ($checks as $check) if ($check['status'] === 'error') $output->writeln('<comment>'.$check['name'].': '.$check['help'].'</comment>');
                $output->writeln($healthy ? '<info>Your environment is ready. Next: php elang serve</info>' : '<error>Fix the checks above, then run doctor again.</error>');
            }
            return $healthy ? 0 : 1;
        }));
        $console->add((new Command('about'))->setDescription('Show the framework and application environment.')->setCode(function ($input, $output) {
            (new Table($output))->setRows([['Framework', 'Elang '.Application::VERSION], ['PHP', PHP_VERSION], ['Environment', $this->app->config->get('environment', 'production')], ['Frontend', 'HTML · CSS · JavaScript'], ['Database', 'Doctrine DBAL'], ['License', 'MIT']])->render(); return 0;
        }));
        $console->add((new Command('routes'))->setAliases(['route:list'])->setDescription('List registered application routes.')->setCode(function ($input, $output) {
            $rows = [];
            foreach ($this->app->router->all() as $name => $route) $rows[] = [implode('|', $route->getMethods()), $route->getPath(), $name];
            (new Table($output))->setHeaders(['Method', 'Path', 'Name'])->setRows($rows)->render(); return 0;
        }));
        $console->add((new Command('key:generate'))->setDescription('Generate and save an application encryption key.')->addOption('force', null, InputOption::VALUE_NONE, 'Replace an existing key (old ciphertext will become unreadable).')->setCode(function ($input, $output) {
            $path = $this->app->basePath.'/.env';
            $env = is_file($path) ? file_get_contents($path) : (is_file($this->app->basePath.'/.env.example') ? file_get_contents($this->app->basePath.'/.env.example') : '');
            if (preg_match('/^APP_KEY=(.+)$/m', $env) && !$input->getOption('force')) { $output->writeln('<error>An application key already exists. Keep it safe; use --force only to rotate it.</error>'); return 1; }
            $line = 'APP_KEY='.base64_encode(random_bytes(32));
            $env = preg_match('/^APP_KEY=.*$/m', $env) ? preg_replace('/^APP_KEY=.*$/m', $line, $env) : rtrim($env)."\n".$line."\n";
            file_put_contents($path, $env, LOCK_EX); chmod($path, 0600);
            $output->writeln('<info>Application key saved in .env. Never commit this file.</info>'); return 0;
        }));
        foreach (['migrate', 'migrate:status', 'migrate:rollback'] as $name) {
            $console->add((new Command($name))->setDescription(match ($name) { 'migrate' => 'Apply pending database migrations.', 'migrate:status' => 'List applied and pending migrations.', default => 'Roll back the latest migration batch.' })->addOption('force', null, InputOption::VALUE_NONE, 'Allow a database change in production.')->setCode(function ($input, $output) use ($name) {
                if ($name !== 'migrate:status' && $this->app->config->get('environment', 'production') === 'production' && !$input->getOption('force')) { $output->writeln('<error>Production database: use --force after reviewing the migrations.</error>'); return 1; }
                $migrator = $this->app->container->get(Migrator::class);
                if ($name === 'migrate:status') {
                    $rows = []; foreach ($migrator->status() as $version => $done) $rows[] = [$version, $done ? 'Applied' : 'Pending'];
                    (new Table($output))->setHeaders(['Migration', 'Status'])->setRows($rows)->render();
                } else {
                    $done = $name === 'migrate' ? $migrator->migrate() : $migrator->rollback();
                    foreach ($done as $version) $output->writeln('<info>'.$version.'</info>');
                    $output->writeln(count($done).' migration(s) processed.');
                } return 0;
            }));
        }
        $console->add((new Command('cache:clear'))->setDescription('Clear the application cache.')->setCode(function ($input, $output) {
            $this->app->container->get(\Psr\SimpleCache\CacheInterface::class)->clear();
            $output->writeln('<info>Application cache cleared.</info>'); return 0;
        }));
        $console->add((new Command('make:controller'))->setDescription('Generate a controller.')->addArgument('name', InputArgument::REQUIRED)->setCode(function ($input, $output) {
            $name = $input->getArgument('name');
            if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/D', $name)) throw new \InvalidArgumentException('Use a simple PascalCase class name.');
            $path = $this->app->basePath.'/app/Controllers/'.$name.'.php';
            if (file_exists($path)) throw new \RuntimeException('Controller already exists.');
            if (!is_dir(dirname($path))) mkdir(dirname($path), 0770, true);
            $code = "<?php\ndeclare(strict_types=1);\nnamespace App\\Controllers;\nuse Elang\\Http\\Response;\nuse Symfony\\Component\\HttpFoundation\\JsonResponse;\nfinal class ".$name."\n{\n    public function __invoke(): JsonResponse\n    {\n        return Response::json(['message' => 'Hello from Elang']);\n    }\n}\n";
            file_put_contents($path, $code); $output->writeln('<info>Created app/Controllers/'.$name.'.php</info>'); return 0;
        }));
        $console->add((new Command('make:migration'))->setDescription('Generate a migration.')->addArgument('name', InputArgument::REQUIRED)->setCode(function ($input, $output) {
            $name = $input->getArgument('name');
            if (!preg_match('/^[a-z][a-z0-9_]*$/D', $name)) throw new \InvalidArgumentException('Use a snake_case migration name.');
            $directory = $this->app->basePath.'/database/migrations'; if (!is_dir($directory)) mkdir($directory, 0770, true);
            $file = date('Y_m_d_His').'_'.bin2hex(random_bytes(2)).'_'.$name.'.php';
            file_put_contents($directory.'/'.$file, "<?php\ndeclare(strict_types=1);\nuse Doctrine\\DBAL\\Connection;\nuse Elang\\Database\\Migration;\nreturn new class implements Migration {\n    public function up(Connection \$database): void {}\n    public function down(Connection \$database): void {}\n};\n");
            $output->writeln('<info>Created '.$file.'</info>'); return 0;
        }));
        $console->add((new Command('new'))->setDescription('Create a standalone Elang application.')->addArgument('directory', InputArgument::REQUIRED)->addOption('local', null, InputOption::VALUE_NONE, 'Include a sanitized local framework source snapshot.')->addOption('example', null, InputOption::VALUE_REQUIRED, 'Include a complete example or starter.')->addOption('starter',null,InputOption::VALUE_REQUIRED,'auth, blog, dashboard, or api')->setCode(function ($input, $output) {
            $path = $input->getArgument('directory');
            if (!str_starts_with($path, '/')) $path = getcwd().'/'.$path;
            if($input->getOption('example')&&$input->getOption('starter')) throw new \InvalidArgumentException('Choose --example or --starter.');
            (new ProjectGenerator())->generate($path, $input->getOption('local') ? dirname(__DIR__, 2) : null, ($input->getOption('starter')??$input->getOption('example')));
            $output->writeln('<info>Application created at '.$path.'</info>');
            $output->writeln('Next: cd into the application, then run composer install, php elang key:generate, and php elang serve.'); return 0;
        }));
        $console->add((new Command('serve'))->setDescription('Run the local development server.')->addOption('host', null, InputOption::VALUE_REQUIRED, 'Listen address', '127.0.0.1')->addOption('port', null, InputOption::VALUE_REQUIRED, 'Listen port', '8000')->setCode(function ($input, $output) {
            $host = $input->getOption('host'); $port = filter_var($input->getOption('port'), FILTER_VALIDATE_INT);
            if (!filter_var($host, FILTER_VALIDATE_IP) && $host !== 'localhost') throw new \InvalidArgumentException('Invalid listen address.');
            if (!$port || $port < 1024 || $port > 65535) throw new \InvalidArgumentException('Port must be between 1024 and 65535.');
            $address = (str_contains($host, ':') ? '['.$host.']' : $host).':'.$port;
            $output->writeln('<info>Elang is running at http://'.$address.'</info>');
            $process = proc_open([PHP_BINARY, '-S', $address, '-t', $this->app->basePath.'/public', $this->app->basePath.'/public/router.php'], [STDIN, STDOUT, STDERR], $pipes, $this->app->basePath);
            return is_resource($process) ? proc_close($process) : 1;
        }));
        $console->add((new Command('user:create'))->setDescription('Provision a user through a trusted terminal; passwords are hidden.')->addArgument('email',InputArgument::REQUIRED)->addOption('name',null,InputOption::VALUE_REQUIRED,'Display name','Administrator')->addOption('admin',null,InputOption::VALUE_NONE,'Assign the administrator role')->addOption('verified',null,InputOption::VALUE_NONE,'Mark a trusted, provisioned email as verified')->addOption('temporary',null,InputOption::VALUE_NONE,'Require a password change at first sign-in')->setCode(function($input,$output):int {
            $question=new \Symfony\Component\Console\Question\Question('Password (hidden): ');$question->setHidden(true);$question->setHiddenFallback(false);
            $password=(new \Symfony\Component\Console\Helper\QuestionHelper())->ask($input,$output,$question);
            if(!is_string($password)||$password==='') throw new \InvalidArgumentException('A password is required.');
            $user=$this->app->container->get(\Elang\Auth\Users::class)->create($input->getOption('name'),$input->getArgument('email'),$password,$input->getOption('admin')?'admin':'user',$input->getOption('verified'),$input->getOption('temporary'));
            $output->writeln('<info>User created: '.$user->email.'. Password value hidden.</info>');return 0;
        }));
        $console->add((new Command('studio'))->setDescription('Show the local development panel URL.')->setCode(function($input,$output):int {
            if($this->app->config->get('environment')!=='local') { $output->writeln('<error>Studio is available only in the local environment.</error>');return 1; }
            $output->writeln('Start php elang serve, then open '.rtrim($this->app->config->get('url','http://localhost:8000'),'/').'/_elang/studio');return 0;
        }));
        $console->add((new Command('make:model'))->setDescription('Generate an explicitly fillable data model.')->addArgument('name',InputArgument::REQUIRED)->addOption('table',null,InputOption::VALUE_REQUIRED,'SQL table name')->setCode(function($input,$output):int {
            $name=$input->getArgument('name');$table=$input->getOption('table');
            if(!preg_match('/^[A-Z][a-zA-Z0-9]*$/D',$name)||!is_string($table)||!preg_match('/^[a-z_][a-z0-9_]*$/D',$table)) throw new \InvalidArgumentException('Use a PascalCase model name and --table=your_table.');
            $path=$this->app->basePath.'/app/Models/'.$name.'.php';if(is_file($path)) throw new \RuntimeException('Model already exists.');if(!is_dir(dirname($path))) mkdir(dirname($path),0770,true);
            file_put_contents($path,"<?php\ndeclare(strict_types=1);\nnamespace App\\Models;\nfinal class ".$name." extends \\Elang\\Data\\Model\n{\n    protected const TABLE = '".$table."';\n    protected const FILLABLE = [];\n    protected const HIDDEN = [];\n    protected const CASTS = ['id'=>'int'];\n}\n");$output->writeln('<info>Created model. Declare FILLABLE before accepting writes.</info>');return 0;
        }));
        $console->add((new Command('db:seed'))->setDescription('Run an application seeder.')->addArgument('class',InputArgument::REQUIRED)->addOption('force',null,InputOption::VALUE_NONE,'Allow production seeding')->setCode(function($input,$output):int {
            if($this->app->config->get('environment')==='production'&&!$input->getOption('force')) throw new \RuntimeException('Review the production seeder, then use --force.');
            $class=$input->getArgument('class');if(!is_subclass_of($class,\Elang\Data\Seeder::class)) throw new \InvalidArgumentException('The class must implement Elang Data Seeder.');
            $seeder=$this->app->container->get($class);$seeder->run($this->app->container->get(\Elang\Database\Database::class),$this->app->container->get(\Elang\Data\Models::class));$output->writeln('<info>Seeder completed.</info>');return 0;
        }));
        if($this->app->container->has(\Elang\Ai\DocumentSource::class)) $console->add((new Command('docs:serve'))->setDescription('Serve the read-only documentation MCP protocol over stdio.')->setCode(function():int {
            $server=$this->app->container->get(\Elang\Ai\DocumentationServer::class);
            while(($line=fgets(STDIN))!==false) {
                try { $response=$server->handle(json_decode($line,true,64,JSON_THROW_ON_ERROR)); }
                catch(\JsonException) { $response=['jsonrpc'=>'2.0','id'=>null,'error'=>['code'=>-32700,'message'=>'Parse error.']]; }
                if($response!==null) fwrite(STDOUT,json_encode($response,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n");
            }return 0;
        }));
        QueueCommands::register($console,$this->app);
        return $console->run();
    }
}
