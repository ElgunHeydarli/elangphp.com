<?php
declare(strict_types=1);
namespace Elang\Security\Account;
use Elang\{Auth\User,Database\Database,Security\Encryption,Support\Config};
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
final class Mfa
{
    public function __construct(private Database $database,private Encryption $encryption,private ClockInterface $clock,private Config $config) {}
    public function enabled(User $user):bool { return (bool)$this->database->connection()->fetchOne('SELECT enabled FROM auth_mfa WHERE user_id = ?',[$user->id]); }
    public function begin(Request $request,User $user):array
    {
        if($this->enabled($user)) throw new \Elang\Http\HttpException(409,'Two-step verification is already enabled.');
        $otp=TOTP::generate($this->clock,20)->withIssuer($this->config->get('name','Elang'))->withLabel($user->email);
        $request->getSession()->set('_mfa_setup',['user'=>$user->id,'secret'=>$this->encryption->encrypt($otp->getSecret()),'expires'=>$this->clock->now()->getTimestamp()+600]);return ['secret'=>$otp->getSecret(),'uri'=>$otp->getProvisioningUri()];
    }
    public function pending(Request $request,User $user):?array
    {
        $pending=$request->getSession()->get('_mfa_setup');if(!is_array($pending)||$pending['user']!==$user->id||$pending['expires']<$this->clock->now()->getTimestamp()) return null;
        $secret=$this->encryption->decrypt($pending['secret']);$otp=TOTP::createFromSecret($secret,$this->clock)->withIssuer($this->config->get('name','Elang'))->withLabel($user->email);return ['secret'=>$secret,'uri'=>$otp->getProvisioningUri()];
    }
    private function step(#[\SensitiveParameter] string $secret,#[\SensitiveParameter] string $code):?int
    {
        if(!preg_match('/^[0-9]{6}$/D',$code)) return null;$now=intdiv($this->clock->now()->getTimestamp(),30);$otp=TOTP::createFromSecret($secret,$this->clock);
        foreach([$now,$now-1,$now+1] as $step) if($step>=0&&$otp->verify($code,$step*30)) return $step;return null;
    }
    public function enable(Request $request,User $user,#[\SensitiveParameter] string $code):?array
    {
        $pending=$this->pending($request,$user);if(!$pending) return null;$step=$this->step($pending['secret'],$code);if($step===null) return null;
        $codes=$this->database->connection()->transactional(function($connection)use($user,$pending,$step):array {
            \Elang\Database\Atomic::insertIfMissing($connection,'auth_mfa',['user_id'=>$user->id,'secret'=>'','last_step'=>-1,'enabled'=>0,'created_at'=>$this->clock->now()->getTimestamp()]);\Elang\Database\Atomic::lockRow($connection,'auth_mfa','user_id',$user->id);
            if((bool)\Elang\Database\Atomic::currentRow($connection,'SELECT enabled FROM auth_mfa WHERE user_id = ?',[$user->id])['enabled']) throw new \Elang\Http\HttpException(409,'Two-step verification changed in another session.');
            $connection->update('auth_mfa',['secret'=>$this->encryption->encrypt($pending['secret']),'last_step'=>$step,'enabled'=>1],['user_id'=>$user->id]);return $this->generateRecovery($user);
        });$request->getSession()->remove('_mfa_setup');return $codes;
    }
    public function verify(User $user,#[\SensitiveParameter] string $code):bool
    {
        $connection=$this->database->connection();$row=$connection->fetchAssociative('SELECT * FROM auth_mfa WHERE user_id = ? AND enabled = ?',[$user->id,1]);if(!$row) return false;
        $normalized=strtolower(str_replace(['-',' '],'',$code));
        if(preg_match('/^[a-f0-9]{20}$/D',$normalized)) return $connection->delete('auth_recovery_codes',['user_id'=>$user->id,'code_hash'=>hash('sha256',$normalized)])===1;
        $step=$this->step($this->encryption->decrypt($row['secret']),$code);if($step===null) return false;
        return $connection->executeStatement('UPDATE auth_mfa SET last_step = ? WHERE user_id = ? AND enabled = ? AND last_step < ?',[$step,$user->id,1,$step])===1;
    }
    public function generateRecovery(User $user):array
    {
        $connection=$this->database->connection();$connection->delete('auth_recovery_codes',['user_id'=>$user->id]);$codes=[];
        for($i=0;$i<10;$i++) { $raw=bin2hex(random_bytes(10));$connection->insert('auth_recovery_codes',['user_id'=>$user->id,'code_hash'=>hash('sha256',$raw)]);$codes[]=implode('-',str_split($raw,4)); }return $codes;
    }
    public function disable(User $user):void { $this->database->connection()->transactional(function($connection)use($user):void { $connection->delete('auth_mfa',['user_id'=>$user->id]);$connection->delete('auth_recovery_codes',['user_id'=>$user->id]); }); }
}
