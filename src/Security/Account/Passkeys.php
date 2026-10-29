<?php
declare(strict_types=1);
namespace Elang\Security\Account;
use Elang\{Auth\User,Auth\Users,Database\Database,Database\Atomic,Support\Config,Http\HttpException};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\{AuthenticatorAssertionResponse,AuthenticatorAssertionResponseValidator,AuthenticatorAttestationResponse,AuthenticatorAttestationResponseValidator,AuthenticatorSelectionCriteria,CredentialRecord,PublicKeyCredential,PublicKeyCredentialCreationOptions,PublicKeyCredentialParameters,PublicKeyCredentialRequestOptions,PublicKeyCredentialRpEntity,PublicKeyCredentialUserEntity};
use Webauthn\AttestationStatement\{AttestationStatementSupportManager,NoneAttestationStatementSupport};
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
final class Passkeys
{
    private ?SerializerInterface $serializer=null;
    public function __construct(private Database $database,private Users $users,private Config $config) {}
    private function serializer():SerializerInterface
    {
        if($this->serializer) return $this->serializer;$support=AttestationStatementSupportManager::create();$support->add(NoneAttestationStatementSupport::create());return $this->serializer=(new WebauthnSerializerFactory($support))->create();
    }
    private function rp():string
    {
        $url=$this->config->get('url');$host=parse_url($url,PHP_URL_HOST);if(!is_string($host)||parse_url($url,PHP_URL_SCHEME)!=='https'&&!in_array($host,['localhost','127.0.0.1','::1'],true)) throw new \LogicException('Passkeys require HTTPS, or localhost during development.');return $host;
    }
    private function factory():CeremonyStepManagerFactory
    {
        $support=AttestationStatementSupportManager::create();$support->add(NoneAttestationStatementSupport::create());$factory=new CeremonyStepManagerFactory();$factory->setAttestationStatementSupportManager($support);
        $origins=$this->config->get('auth.passkey_origins',[rtrim($this->config->get('url'),'/')]);if(!is_array($origins)||!$origins) throw new \LogicException('Configure explicit passkey origins.');$factory->setAllowedOrigins($origins,false);return $factory;
    }
    private function saveChallenge(Request $request,string $mode,string $json,?User $user,string $name=''):array
    {
        $request->getSession()->set('_passkey_challenge',['mode'=>$mode,'options'=>$json,'user'=>$user?->id,'version'=>$user?->authVersion,'expires'=>time()+120,'name'=>$name]);return json_decode($json,true,64,JSON_THROW_ON_ERROR);
    }
    private function challenge(Request $request,string $mode,?User $user=null):array
    {
        $challenge=$request->getSession()->remove('_passkey_challenge');if(!is_array($challenge)||$challenge['mode']!==$mode||$challenge['expires']<time()||$challenge['user']!==$user?->id||$challenge['version']!==$user?->authVersion) throw new HttpException(422,'The passkey request expired. Start again.');return $challenge;
    }
    public function registrationOptions(Request $request,User $user,string $name):array
    {
        if(!$user->verified||$user->mustChangePassword) throw new HttpException(403,'Verify your account and update any temporary password first.');
        $name=trim($name);if($name===''||mb_strlen($name)>80||count($this->list($user))>=20) throw new HttpException(422,'Use a passkey name of 1–80 characters. An account can have up to 20 passkeys.');
        $connection=$this->database->connection();Atomic::insertIfMissing($connection,'auth_passkey_handles',['user_id'=>$user->id,'handle'=>base64_encode(random_bytes(32))]);$handle=base64_decode($connection->fetchOne('SELECT handle FROM auth_passkey_handles WHERE user_id = ?',[$user->id]),true);
        $descriptors=[];foreach($connection->fetchFirstColumn('SELECT record_json FROM auth_passkeys WHERE user_id = ?',[$user->id]) as $json) $descriptors[]=$this->serializer()->deserialize($json,CredentialRecord::class,'json')->getPublicKeyCredentialDescriptor();
        $options=PublicKeyCredentialCreationOptions::create(rp:PublicKeyCredentialRpEntity::create($this->config->get('name','Elang'),$this->rp()),user:PublicKeyCredentialUserEntity::create($user->email,$handle,$user->name),challenge:random_bytes(32),pubKeyCredParams:[PublicKeyCredentialParameters::create('public-key',-7),PublicKeyCredentialParameters::create('public-key',-257)],timeout:120000,authenticatorSelection:AuthenticatorSelectionCriteria::create(userVerification:'required',residentKey:'required'),attestation:'none',excludeCredentials:$descriptors);
        return $this->saveChallenge($request,'register',$this->serializer()->serialize($options,'json',['skip_null_values'=>true]),$user,$name);
    }
    public function register(Request $request,User $user,array $browserCredential):void
    {
        $challenge=$this->challenge($request,'register',$user);
        try {
            $credential=$this->serializer()->deserialize(json_encode($browserCredential,JSON_THROW_ON_ERROR),PublicKeyCredential::class,'json');if(!$credential->response instanceof AuthenticatorAttestationResponse) throw new \InvalidArgumentException('Wrong passkey response.');
            $options=$this->serializer()->deserialize($challenge['options'],PublicKeyCredentialCreationOptions::class,'json');$record=AuthenticatorAttestationResponseValidator::create($this->factory()->creationCeremony())->check($credential->response,$options,$this->rp());
            $this->database->connection()->insert('auth_passkeys',['credential_hash'=>hash('sha256',$record->publicKeyCredentialId),'user_id'=>$user->id,'name'=>$challenge['name'],'record_json'=>$this->serializer()->serialize($record,'json'),'version'=>1,'created_at'=>time(),'last_used_at'=>null]);
        }catch(\Throwable $error) { if($error instanceof HttpException) throw $error;throw new HttpException(422,'That passkey could not be registered. Start again or use another device.'); }
    }
    public function authenticationOptions(Request $request):array
    {
        $options=PublicKeyCredentialRequestOptions::create(challenge:random_bytes(32),rpId:$this->rp(),userVerification:'required',timeout:120000);return $this->saveChallenge($request,'authenticate',$this->serializer()->serialize($options,'json',['skip_null_values'=>true]),null);
    }
    public function authenticate(Request $request,array $browserCredential):?User
    {
        $challenge=$this->challenge($request,'authenticate');
        try {
            $credential=$this->serializer()->deserialize(json_encode($browserCredential,JSON_THROW_ON_ERROR),PublicKeyCredential::class,'json');if(!$credential->response instanceof AuthenticatorAssertionResponse) return null;
            $hash=hash('sha256',$credential->rawId);$connection=$this->database->connection();$row=$connection->fetchAssociative('SELECT * FROM auth_passkeys WHERE credential_hash = ?',[$hash]);if(!$row) return null;
            $user=$this->users->find((int)$row['user_id']);if(!$user||!$user->verified||$user->mustChangePassword) return null;
            $record=$this->serializer()->deserialize($row['record_json'],CredentialRecord::class,'json');if(!hash_equals($record->publicKeyCredentialId,$credential->rawId)) return null;
            $options=$this->serializer()->deserialize($challenge['options'],PublicKeyCredentialRequestOptions::class,'json');$updated=AuthenticatorAssertionResponseValidator::create($this->factory()->requestCeremony())->check($record,$credential->response,$options,$this->rp(),$record->userHandle);
            $changed=$connection->executeStatement('UPDATE auth_passkeys SET record_json = ?, version = version + 1, last_used_at = ? WHERE credential_hash = ? AND version = ?',[$this->serializer()->serialize($updated,'json'),time(),$hash,(int)$row['version']]);return $changed===1?$user:null;
        }catch(\Throwable) { return null; }
    }
    public function list(User $user):array { return $this->database->connection()->fetchAllAssociative('SELECT credential_hash, name, created_at, last_used_at FROM auth_passkeys WHERE user_id = ? ORDER BY created_at DESC',[$user->id]); }
    public function remove(User $user,string $hash):bool { return $this->database->connection()->delete('auth_passkeys',['user_id'=>$user->id,'credential_hash'=>$hash])===1; }
}
