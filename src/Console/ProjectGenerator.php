<?php
declare(strict_types=1);
namespace Elang\Console;
final class ProjectGenerator
{
    public const STARTERS=['task-board','auth','blog','dashboard','api','react','next','saas'];
    private function files(string $source): array
    {
        if(!is_dir($source)) return [];$files=[];
        foreach(new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(new \RecursiveDirectoryIterator($source,\FilesystemIterator::SKIP_DOTS),fn($file)=>!$file->isDir()||!in_array($file->getFilename(),['vendor','storage','node_modules','.next','.vite','dist','coverage','.git'],true))) as $file) {
            if(!$file->isFile()) continue;$relative=substr($file->getPathname(),strlen($source)+1);
            if(preg_match('~(^|/)(vendor|storage|node_modules|\.next|\.vite|dist|coverage|\.git)(/|$)|(^|/)\.env($|\.)~',$relative)&&basename($relative)!=='.env.example') continue;
            $files[]=$relative;
        }return $files;
    }
    private function copy(string $source,string $destination,array $files): void
    {
        foreach($files as $relative) {
            if(!is_file($source.'/'.$relative)) continue;$target=$destination.'/'.$relative;
            if(!is_dir(dirname($target))) mkdir(dirname($target),0770,true);
            if(!copy($source.'/'.$relative,$target)) throw new \RuntimeException('Could not create '.$relative);
        }
    }
    private function stageFramework(string $source,string $destination): void
    {
        mkdir($destination,0770,true);$files=['composer.json','.env.example','LICENSE','README.md','bin/elang','compose.yaml','.dockerignore','public/.htaccess','public/router.php'];
        foreach(['src','resources/elang','examples','devkit'] as $folder) foreach($this->files($source.'/'.$folder) as $relative) $files[]=$folder.'/'.$relative;
        foreach(['task-board.css','favicon.svg'] as $file) $files[]='public/assets/'.$file;
        $this->copy($source,$destination,$files);
    }
    public function generate(string $directory,?string $frameworkPath=null,?string $example=null): void
    {
        if($example!==null&&!in_array($example,self::STARTERS,true)) throw new \InvalidArgumentException('Available starters: '.implode(', ',self::STARTERS));
        if(is_dir($directory)&&count(scandir($directory))>2) throw new \RuntimeException('Destination must be empty.');
        if(!is_dir($directory)&&!mkdir($directory,0770,true)) throw new \RuntimeException('Could not create destination.');
        $composer=['name'=>'app/elang-project','type'=>'project','license'=>'MIT','require'=>['php'=>'^8.3','elang/framework'=>$frameworkPath?'dev-main':\Elang\Application::VERSION],'autoload'=>['psr-4'=>['App\\'=>'app/']],'repositories'=>[$frameworkPath?['type'=>'path','url'=>'./.elang/framework','options'=>['symlink'=>false,'versions'=>['elang/framework'=>'dev-main']]]:['type'=>'composer','url'=>'https://elangphp.com/packages']],'minimum-stability'=>'dev','prefer-stable'=>true,'config'=>['allow-plugins'=>false]];
        if($frameworkPath) $this->stageFramework($frameworkPath,$directory.'/.elang/framework');
        $files=[
            'composer.json'=>json_encode($composer,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n",
            'bootstrap/app.php'=>"<?php\ndeclare(strict_types=1);\n\$app=Elang\\Application::create(dirname(__DIR__));\n\$router=\$app->router;\nrequire dirname(__DIR__).'/routes/web.php';\nreturn \$app;\n",
            'routes/web.php'=>"<?php\ndeclare(strict_types=1);\nuse Elang\\Http\\Response;\n\$router->get('/',fn()=>Response::html('<!doctype html><html lang=\"en\"><title>Welcome to Elang</title><h1>Your next idea starts here.</h1><p>Built with Elang.</p></html>'),'home');\n\$router->get('/api/hello/{name}',fn(string \$name)=>['message'=>'Hello, '.\$name.'!'],'hello');\n",
            'public/index.php'=>"<?php\ndeclare(strict_types=1);\nrequire dirname(__DIR__).'/vendor/autoload.php';\n\$app=require dirname(__DIR__).'/bootstrap/app.php';\n\$app->run();\n",
            'elang'=>"#!/usr/bin/env php\n<?php\ndeclare(strict_types=1);\nrequire __DIR__.'/vendor/autoload.php';\n\$app=require __DIR__.'/bootstrap/app.php';\nexit((new Elang\\Console\\Kernel(\$app))->run());\n",
            '.env.example'=>file_get_contents(dirname(__DIR__,2).'/.env.example'),
            '.gitignore'=>"/vendor/\n/.env\n/storage/*\n/database/*.sqlite*\n**/node_modules/\n**/.next/\n**/dist/\n**/.env.local\n",
            'README.md'=>"# Your Elang application\n\nFor a locally generated project, run composer install, php elang key:generate, and php elang doctor. Then run php elang serve.\n\nExpose only public/. Documentation: https://elangphp.com/docs/v0.1/introduction\n",
            'resources/views/.gitkeep'=>'','database/migrations/.gitkeep'=>'','app/Controllers/.gitkeep'=>'','storage/.gitkeep'=>'',
        ];
        foreach(['.htaccess','router.php'] as $file) $files['public/'.$file]=file_get_contents(dirname(__DIR__,2).'/public/'.$file);
        foreach($files as $name=>$content) { $path=$directory.'/'.$name;if(!is_dir(dirname($path))) mkdir(dirname($path),0770,true);file_put_contents($path,$content); }
        if(in_array($example,['auth','blog','dashboard','api','react','next','saas'],true)) {
            $common=dirname(__DIR__,2).'/examples/auth';$this->copy($common,$directory,$this->files($common));
        }
        if(in_array($example,['react','next','saas'],true)) { $source=dirname(__DIR__,2).'/examples/api';$this->copy($source,$directory,$this->files($source)); }
        if(in_array($example,['react','next'],true)) { $source=dirname(__DIR__,2).'/examples/frontend-'.$example;$this->copy($source,$directory.'/frontend',$this->files($source)); }
        if($example!==null&&!in_array($example,['react','next'],true)) { $source=dirname(__DIR__,2).'/examples/'.$example;$this->copy($source,$directory,$this->files($source)); }
        if(in_array($example,['react','next','saas'],true)) { $this->copy(dirname(__DIR__,2).'/devkit',$directory.'/devkit',$this->files(dirname(__DIR__,2).'/devkit'));copy(dirname(__DIR__,2).'/compose.yaml',$directory.'/compose.yaml');copy(dirname(__DIR__,2).'/.dockerignore',$directory.'/.dockerignore'); }
        chmod($directory.'/elang',0755);
    }
}
