<?php
declare(strict_types=1);
namespace Elang\Orm;
use Elang\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument,InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
final class Commands
{
    public function __construct(private Application $app) {}
    public function all():array
    {
        return [
            (new Command('orm:cache:clear'))->setDescription('Clear private ORM metadata after deploying changed mappings.')->setCode(function(InputInterface $input,OutputInterface $output):int{$this->app->container->get(Orm::class)->clearMetadataCache();$output->writeln('ORM metadata cleared. Rebuild PHP 8.3 proxies and restart long-running workers.');return 0;}),
            (new Command('orm:validate'))->setDescription('Validate optional Doctrine entity mappings without changing the database.')->setCode(function(InputInterface $input,OutputInterface $output):int{
                $errors=(new \Doctrine\ORM\Tools\SchemaValidator($this->app->container->get(Orm::class)->manager()))->validateMapping();
                foreach($errors as $class=>$messages)foreach($messages as $message)$output->writeln($class.': '.$message);
                $output->writeln($errors?'Entity mapping validation failed.':'Entity mappings are valid.');return $errors?1:0;
            }),
            (new Command('orm:schema:sql'))->setDescription('Print initial schema SQL for review; does not execute SQL.')->setCode(function(InputInterface $input,OutputInterface $output):int{foreach($this->app->container->get(Orm::class)->schemaSql() as $sql)$output->writeln($sql.';');return 0;}),
            (new Command('orm:proxies'))->setDescription('Prepare private ORM proxies for PHP 8.3 production deployments.')->setCode(function(InputInterface $input,OutputInterface $output):int{$orm=$this->app->container->get(Orm::class);$manager=$orm->manager();$count=$manager->getProxyFactory()->generateProxyClasses($manager->getMetadataFactory()->getAllMetadata(),$orm->proxyDirectory());$output->writeln('Prepared '.$count.' proxy files. PHP 8.4 native lazy objects do not require proxy files.');return 0;}),
            (new Command('make:entity'))->setDescription('Create a new attribute-mapped entity without overwriting existing code.')->addArgument('name',InputArgument::REQUIRED,'Simple entity class, such as Product')->setCode(function(InputInterface $input,OutputInterface $output):int{
                $name=(string)$input->getArgument('name');$tokens=token_get_all('<?php class '.$name.' {}');
                if(!preg_match('/^[A-Z][A-Za-z0-9_]*$/D',$name)||!isset($tokens[3][0])||$tokens[3][0]!==T_STRING||in_array(strtolower($name),['int','float','bool','string','array','object','callable','iterable','mixed','never','void','null','true','false','self','parent','static','resource'],true))throw new \InvalidArgumentException('Use a simple, non-reserved PHP class name.');
                $directory=$this->app->basePath.'/app/Entities';$file=$directory.'/'.$name.'.php';if(file_exists($file))throw new \RuntimeException('Entity already exists; existing code was preserved.');
                if(!is_dir($directory))mkdir($directory,0770,true);
                $table=\Doctrine\Inflector\InflectorFactory::create()->build()->pluralize(\Doctrine\Inflector\InflectorFactory::create()->build()->tableize($name));
                $source=<<<'PHP'
<?php
declare(strict_types=1);
namespace App\Entities;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: '{{table}}')]
class {{name}}
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    public ?int $id = null;
}
PHP;
                $handle=@fopen($file,'x');if($handle===false)throw new \RuntimeException('Cannot exclusively create the entity; existing code was preserved.');
                try{fwrite($handle,str_replace(['{{table}}','{{name}}'],[$table,$name],$source)."\n");}finally{fclose($handle);}
                $output->writeln('Created app/Entities/'.$name.'.php. Add mapped fields, then run orm:validate.');return 0;
            }),
        ];
    }
}
