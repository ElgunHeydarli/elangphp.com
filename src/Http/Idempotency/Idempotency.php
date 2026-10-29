<?php
declare(strict_types=1);
namespace Elang\Http\Idempotency;
use Closure;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Elang\{Contracts\Middleware,Database\Database,Http\HttpException,Auth\User};
use Symfony\Component\HttpFoundation\{Request,Response,StreamedResponse};
final class Idempotency implements Middleware
{
    public function __construct(private Database $database) {}
    private function replay(array $row,string $fingerprint):Response
    {
        if(!hash_equals($row['request_hash'],$fingerprint)) throw new HttpException(409,'This idempotency key was already used with different input.');
        if((int)$row['response_status']===0) throw new HttpException(409,'This request is being processed. Retry shortly.');
        return new Response($row['response_body'],(int)$row['response_status'],json_decode($row['response_headers'],true,32,JSON_THROW_ON_ERROR)+['Idempotency-Replayed'=>'true','Cache-Control'=>'private, no-store']);
    }
    public function process(Request $request,Closure $next):Response
    {
        $key=$request->headers->get('Idempotency-Key');if($request->isMethodSafe()||$key===null) return $next($request);
        if(!preg_match('/^[a-zA-Z0-9_.:-]{8,128}$/D',$key)) throw new HttpException(400,'Use an Idempotency-Key of 8–128 letters, numbers, or . _ : - characters.');
        $user=$request->attributes->get('_user');if(!$user instanceof User) throw new HttpException(403,'Idempotency requires an authenticated user.');
        $hash=hash('sha256',$user->id.'|'.$request->getMethod().'|'.$request->getPathInfo().'|'.$key);$fingerprint=hash('sha256',$request->getContent().'|'.json_encode($request->request->all(),JSON_THROW_ON_ERROR).'|'.$request->getQueryString());$connection=$this->database->connection();
        $existing=$connection->fetchAssociative('SELECT * FROM elang_idempotency WHERE key_hash = ?',[$hash]);if($existing) return $this->replay($existing,$fingerprint);
        try {
            $connection->beginTransaction();
            $connection->insert('elang_idempotency',['key_hash'=>$hash,'request_hash'=>$fingerprint,'response_body'=>'','response_status'=>0,'response_headers'=>'{}','created_at'=>time()]);
            $response=$next($request);
            if($response->getStatusCode()<200||$response->getStatusCode()>=300) { $connection->rollBack();return $response; }
            if($response instanceof StreamedResponse||!str_contains($response->headers->get('Content-Type',''),'application/json')&&$response->getStatusCode()!==204||strlen($response->getContent())>1048576) throw new \LogicException('Idempotency supports buffered JSON responses up to 1 MB and 204 responses.');
            $headers=[];foreach(['Content-Type','Location'] as $name) if($response->headers->has($name)) $headers[$name]=$response->headers->get($name);
            $connection->update('elang_idempotency',['response_body'=>$response->getContent(),'response_status'=>$response->getStatusCode(),'response_headers'=>json_encode($headers,JSON_THROW_ON_ERROR)],['key_hash'=>$hash]);$connection->commit();$response->headers->set('Cache-Control','private, no-store');return $response;
        } catch(\Throwable $error) {
            if($connection->isTransactionActive()) $connection->rollBack();
            if($error instanceof UniqueConstraintViolationException) { $row=$connection->fetchAssociative('SELECT * FROM elang_idempotency WHERE key_hash = ?',[$hash]);if($row) return $this->replay($row,$fingerprint); }
            throw $error;
        }
    }
}
