<?php
declare(strict_types=1);
namespace App\Integrations;
use Elang\{Application,Security\Encryption};
use GuzzleHttp\ClientInterface;
final class Packagist
{
    public function __construct(private Application $app,private Encryption $encryption,private ClientInterface $http) {}
    private function path():string { return $this->app->config->get('paths.storage').'/integrations/packagist.sealed'; }
    public function settings():array
    {
        if(!is_file($this->path())) return ['configured'=>false,'username'=>'ElgunHeydarli'];try { $value=json_decode($this->encryption->decrypt(file_get_contents($this->path())),true,8,JSON_THROW_ON_ERROR);return ['configured'=>true,'username'=>$value['username']]; }catch(\Throwable) { return ['configured'=>false,'username'=>'ElgunHeydarli']; }
    }
    public function save(string $username,#[\SensitiveParameter] string $token):void
    {
        if(!preg_match('/^[A-Za-z0-9_.-]{2,80}$/D',$username)||strlen($token)<16||strlen($token)>512||preg_match('/\s/',$token)) throw new \InvalidArgumentException('Enter the Packagist username and main API token from your profile.');$directory=dirname($this->path());if(!is_dir($directory)) mkdir($directory,0700,true);$sealed=$this->encryption->encrypt(json_encode(['username'=>$username,'token'=>$token],JSON_THROW_ON_ERROR));$tmp=$this->path().'.'.bin2hex(random_bytes(4));file_put_contents($tmp,$sealed,LOCK_EX);chmod($tmp,0600);rename($tmp,$this->path());
    }
    public function manifest():array
    {
        $path=$this->app->basePath.'/config/distribution-repositories.json';if(!is_file($path)) return [];$rows=json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR);return array_values(array_filter($rows,fn($row)=>isset($row['name'],$row['repository'],$row['commit'])&&preg_match('~^elang/[a-z0-9-]+$~D',$row['name'])&&preg_match('~^https://github\.com/ElgunHeydarli/elang-[a-z0-9-]+$~D',$row['repository'])&&preg_match('/^[a-f0-9]{40}$/D',$row['commit'])));
    }
    public function submit(string $name):array
    {
        $prepared=array_column($this->manifest(),null,'name');$package=$prepared[$name]??throw new \InvalidArgumentException('Choose a prepared official package.');if(!is_file($this->path())) throw new \RuntimeException('Save the encrypted Packagist connection first.');$credentials=json_decode($this->encryption->decrypt(file_get_contents($this->path())),true,8,JSON_THROW_ON_ERROR);
        try {
            $existing=$this->http->request('GET','https://packagist.org/packages/'.$name.'.json',['http_errors'=>false,'allow_redirects'=>false,'connect_timeout'=>2,'timeout'=>5]);$code=$existing->getStatusCode();
            if($code===200) { $body=json_decode((string)$existing->getBody(),true,64,JSON_THROW_ON_ERROR);if(preg_replace('~\.git$~','',rtrim($body['package']['repository']??'','/'))!==preg_replace('~\.git$~','',rtrim($package['repository'],'/'))) throw new \RuntimeException('That package name belongs to another source repository.');$endpoint='https://packagist.org/api/update-package'; }elseif($code===404) $endpoint='https://packagist.org/api/create-package';else throw new \RuntimeException('Packagist metadata is temporarily unavailable.');
            $response=$this->http->request('POST',$endpoint,['http_errors'=>false,'allow_redirects'=>false,'connect_timeout'=>2,'timeout'=>8,'headers'=>['Authorization'=>'Bearer '.$credentials['username'].':'.$credentials['token'],'User-Agent'=>'Elang-Package-Publisher/0.1 (+https://elangphp.com/community)','Accept'=>'application/json'],'json'=>['repository'=>$package['repository']]]);$result=json_decode((string)$response->getBody(),true,16,JSON_THROW_ON_ERROR);
            if($response->getStatusCode()>=200&&$response->getStatusCode()<300&&($result['status']??'')==='success') return ['name'=>$name,'state'=>'submitted','message'=>'Submitted. Packagist indexing and version availability still need verification.'];
            return ['name'=>$name,'state'=>'failed','message'=>in_array($response->getStatusCode(),[401,403],true)?'Packagist rejected the account token or permission. Use the main API token.':'Packagist did not accept this package. Check namespace ownership and the repository manifest.'];
        }catch(\RuntimeException $e) { if($e->getMessage()==='That package name belongs to another source repository.') throw $e;throw new \RuntimeException('Packagist could not complete the request. Check the connection and try again.'); }catch(\Throwable) { throw new \RuntimeException('Packagist could not complete the request. Check the connection and try again.'); }
    }
}
