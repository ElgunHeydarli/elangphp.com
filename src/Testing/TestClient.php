<?php
declare(strict_types=1);
namespace Elang\Testing;
use Elang\Application;
use Elang\Http\Client\{Client,FakeTransport};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mailer\{Mailer,MailerInterface};

/** In-process kernel requests, isolated sessions, and no default outbound mail/HTTP. */
final class TestClient
{
    private Session $session;
    private array $headers=[];
    private array $cookies=[];
    private bool $csrf=false;
    public readonly MailRecorder $mail;
    public readonly FakeTransport $http;
    public function __construct(private Application $app)
    {
        if($app->config->get('environment')!=='testing')throw new \LogicException('TestClient requires an explicitly configured testing application and isolated database.');
        $this->session=new Session(new MockArraySessionStorage());
        if($app->container->has('elang.testing.mail')){
            $this->mail=$app->container->get('elang.testing.mail');$this->http=$app->container->get('elang.testing.http');
        }else{
            $this->mail=new MailRecorder();$this->http=new FakeTransport();
            $app->container->instance('elang.testing.mail',$this->mail);$app->container->instance('elang.testing.http',$this->http);
            $app->container->instance(MailerInterface::class,new Mailer($this->mail));$app->container->instance(Client::class,new Client($this->http));
        }
    }
    public function withHeaders(array $headers):self { foreach($headers as $name=>$value)$this->headers[strtolower($name)]=$value;return $this; }
    public function withSession(array $values):self { foreach($values as $key=>$value)$this->session->set($key,$value);return $this; }
    public function withCsrf(bool $enabled=true):self { $this->csrf=$enabled;return $this; }
    public function actingAs(\Elang\Auth\User $user):self
    {
        $request=Request::create($this->app->config->get('url','http://localhost').'/');$request->setSession($this->session);$this->app->container->get(\Elang\Auth\Auth::class)->login($request,$user);return $this;
    }
    public function get(string $path,array $query=[]):TestResponse { return $this->request('GET',$path,$query); }
    public function post(string $path,array $data=[]):TestResponse { return $this->request('POST',$path,$data); }
    public function json(string $method,string $path,array $data=[]):TestResponse { return $this->request($method,$path,[],['Content-Type'=>'application/json','Accept'=>'application/json'],json_encode($data,JSON_THROW_ON_ERROR)); }
    public function request(string $method,string $path,array $parameters=[],array $headers=[],?string $body=null):TestResponse
    {
        if(!str_starts_with($path,'/')||str_starts_with($path,'//')||preg_match('/[\r\n\x00]/',$path))throw new \InvalidArgumentException('TestClient uses local application paths only.');
        $url=rtrim($this->app->config->get('url','http://localhost'),'/').$path;
        $all=$this->headers;foreach($headers as $name=>$value)$all[strtolower($name)]=$value;
        $server=[];foreach($all as $name=>$value)$server[in_array($name,['content-type','content-length'],true)?strtoupper(str_replace('-','_',$name)):'HTTP_'.strtoupper(str_replace('-','_',$name))]=$value;
        $request=Request::create($url,strtoupper($method),$parameters,$this->cookies,[],$server,$body);$request->setSession($this->session);
        if($this->csrf&&!in_array(strtoupper($method),['GET','HEAD','OPTIONS'],true)){
            $request->headers->set('X-CSRF-Token',$this->app->container->get(\Elang\Security\Csrf::class)->token($request));
            if(!$request->headers->has('Origin'))$request->headers->set('Origin',$request->getSchemeAndHttpHost());
        }
        $response=$this->app->handle($request);
        foreach($response->headers->getCookies() as $cookie){if($cookie->isCleared())unset($this->cookies[$cookie->getName()]);else $this->cookies[$cookie->getName()]=$cookie->getValue();}
        return new TestResponse($response);
    }
    public function assertDatabaseHas(string $table,array $values):void
    {
        if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D',$table)||!$values)throw new \InvalidArgumentException('Use a table and at least one known column.');
        $db=$this->app->container->get(\Elang\Database\Database::class)->connection();$query=$db->createQueryBuilder()->select('COUNT(*)')->from($db->quoteIdentifier($table));
        foreach($values as $column=>$value){if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D',$column))throw new \InvalidArgumentException('Invalid assertion column.');if($value===null)$query->andWhere($db->quoteIdentifier($column).' IS NULL');else $query->andWhere($db->quoteIdentifier($column).' = :'.$column)->setParameter($column,$value);}
        Assert::truth((int)$query->fetchOne()>0,'Expected database record was not found.');
    }
    public function __debugInfo():array { return ['application'=>'testing','cookies'=>array_keys($this->cookies),'session'=>'[redacted]']; }
}
