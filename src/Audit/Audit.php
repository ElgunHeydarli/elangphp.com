<?php
declare(strict_types=1);
namespace Elang\Audit;
use Elang\{Database\Database,Database\Atomic,Support\Config};
final class Audit
{
    public function __construct(private Database $database,private Config $config) {}
    private function key():string { $raw=base64_decode($this->config->get('key',''),true);if($raw===false||strlen($raw)!==32) throw new \LogicException('A valid application key is required for audit integrity.');return hash_hmac('sha256','elang.audit.v1',$raw,true); }
    public function append(string $scope,string $action,?int $actor,array $metadata=[]):void
    {
        if(!preg_match('/^[a-zA-Z0-9:_.-]{1,80}$/D',$scope)||!preg_match('/^[a-z][a-z0-9_.-]{1,79}$/D',$action)) throw new \InvalidArgumentException('Invalid audit scope or action.');
        foreach($metadata as $key=>$value) if(!is_string($key)||preg_match('/password|secret|token|cookie|authorization|email|body|payload/i',$key)||!is_scalar($value)&&$value!==null) throw new \InvalidArgumentException('Audit metadata accepts safe scalar identifiers, not credentials or payloads.');
        ksort($metadata);$json=json_encode($metadata,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);if(strlen($json)>2048) throw new \InvalidArgumentException('Audit metadata must fit within 2 KB.');
        $this->database->connection()->transactional(function($connection)use($scope,$action,$actor,$json):void {
            Atomic::insertIfMissing($connection,'elang_audit_heads',['scope'=>$scope,'last_hash'=>str_repeat('0',64),'sequence'=>0]);$head=Atomic::currentRow($connection,'SELECT * FROM elang_audit_heads WHERE scope = ?',[$scope]);
            $entry=['scope'=>$scope,'sequence'=>(int)$head['sequence']+1,'actor_id'=>$actor,'action'=>$action,'metadata'=>$json,'created_at'=>time(),'previous_hash'=>$head['last_hash']];$entry['entry_hash']=hash_hmac('sha256',json_encode(array_values($entry),JSON_THROW_ON_ERROR),$this->key());
            $connection->insert('elang_audit',$entry);$connection->update('elang_audit_heads',['last_hash'=>$entry['entry_hash'],'sequence'=>$entry['sequence']],['scope'=>$scope]);
        });
    }
    public function entries(string $scope,int $limit=100):array { if($limit<1||$limit>200) throw new \InvalidArgumentException('Audit limit must be 1–200.');return $this->database->connection()->createQueryBuilder()->select('*')->from('elang_audit')->where('scope = ?')->setParameter(0,$scope)->orderBy('sequence','DESC')->setMaxResults($limit)->executeQuery()->fetchAllAssociative(); }
    public function verify(string $scope):array
    {
        $connection=$this->database->connection();$previous=str_repeat('0',64);$sequence=0;
        $rows=$connection->iterateAssociative('SELECT * FROM elang_audit WHERE scope = ? ORDER BY sequence',[$scope]);
        foreach($rows as $row) {
            $entry=[$row['scope'],(int)$row['sequence'],$row['actor_id']===null?null:(int)$row['actor_id'],$row['action'],$row['metadata'],(int)$row['created_at'],$row['previous_hash']];$hash=hash_hmac('sha256',json_encode($entry,JSON_THROW_ON_ERROR),$this->key());
            if((int)$row['sequence']!==$sequence+1||!hash_equals($previous,$row['previous_hash'])||!hash_equals($hash,$row['entry_hash'])) return ['valid'=>false,'sequence'=>(int)$row['sequence']];$previous=$hash;$sequence++;
        }
        $head=$connection->fetchAssociative('SELECT * FROM elang_audit_heads WHERE scope = ?',[$scope]);return ['valid'=>!$head?$sequence===0:hash_equals($head['last_hash'],$previous)&&(int)$head['sequence']===$sequence,'sequence'=>$sequence,'hash'=>$previous];
    }
}
