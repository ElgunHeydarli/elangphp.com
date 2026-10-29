<?php
declare(strict_types=1);
namespace Elang\Auth;
use Elang\Database\Database;
use Elang\Validation\Validator;
use Symfony\Component\Validator\Constraints as Assert;
final class Recovery
{
    public function __construct(private Users $users,private LinkTokens $tokens,private Notifications $notifications,private Throttle $throttle,private Database $database,private Validator $validator) {}
    public function requestReset(string $email,string $ip): void
    {
        $this->throttle->consume('reset',$ip,$email);
        if(!$this->notifications->available()) throw new \Elang\Http\HttpException(503,'Password recovery is temporarily unavailable. Contact the site administrator.');
        $row=$this->users->rowByEmail($email);
        if($row) { $user=User::fromRow($row);$this->notifications->send($user,'reset',$this->tokens->issue($user,'reset')); }
    }
    public function reset(string $token,#[\SensitiveParameter] string $password): bool
    {
        $this->validator->validate(['password'=>$password],['password'=>[new Assert\Length(min:12,max:256)]]);
        return $this->database->connection()->transactional(function() use($token,$password):bool {
            $id=$this->tokens->consume($token,'reset');if(!$id) return false;
            $user=$this->users->find($id);if(!$user) return false;
            $this->users->changePassword($user,$password);return true;
        });
    }
    public function sendVerification(User $user,string $ip): void
    {
        $this->throttle->consume('verify',$ip,$user->email);
        if(!$user->verified) $this->notifications->send($user,'verify',$this->tokens->issue($user,'verify'));
    }
    public function verify(string $token): bool
    {
        return $this->database->connection()->transactional(function($connection) use($token):bool {
            $id=$this->tokens->consume($token,'verify');if(!$id) return false;
            return $connection->update('auth_users',['verified_at'=>time()],['id'=>$id])===1;
        });
    }
}
