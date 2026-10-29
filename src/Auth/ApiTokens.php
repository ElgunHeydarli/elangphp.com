<?php
declare(strict_types=1);
namespace Elang\Auth;
use Elang\Database\Database;
use Elang\Support\Config;
final class ApiTokens
{
    public function __construct(private Database $database,private Users $users,private Config $config) {}
    public function issue(User $user,string $name,array $scopes,int $days=30): string
    {
        if($user->mustChangePassword||!$user->verified) throw new \Elang\Http\HttpException(403,'Verify your email and update any temporary password before creating an API token.');
        if(trim($name)===''||mb_strlen($name)>80||$days<1||$days>365||!$scopes) throw new \InvalidArgumentException('Invalid token name, lifetime, or scopes.');
        $allowed=$this->config->get('auth.token_scopes',['notes:read','notes:write']);
        foreach($scopes as $scope) if(!in_array($scope,$allowed,true)) throw new \InvalidArgumentException('Unknown token scope.');
        $token=bin2hex(random_bytes(32));
        $this->database->connection()->insert('auth_api_tokens',['user_id'=>$user->id,'name'=>trim($name),'token_hash'=>hash('sha256',$token),'scopes'=>json_encode(array_values(array_unique($scopes)),JSON_THROW_ON_ERROR),'expires_at'=>time()+$days*86400,'created_at'=>time()]);
        return $token;
    }
    public function authenticate(#[\SensitiveParameter] string $token): ?AccessToken
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$token)) return null;
        $row=$this->database->connection()->fetchAssociative('SELECT * FROM auth_api_tokens WHERE token_hash = ? AND expires_at > ?',[hash('sha256',$token),time()]);
        if(!$row) return null;$user=$this->users->find((int)$row['user_id']);
        if(!$user||!$user->verified||$user->mustChangePassword) return null;
        return new AccessToken((int)$row['id'],$user,json_decode($row['scopes'],true,512,JSON_THROW_ON_ERROR));
    }
    public function list(User $user): array { return $this->database->connection()->fetchAllAssociative('SELECT id, name, scopes, expires_at, created_at FROM auth_api_tokens WHERE user_id = ? ORDER BY id DESC',[$user->id]); }
    public function revoke(User $user,int $id): bool { return $this->database->connection()->delete('auth_api_tokens',['user_id'=>$user->id,'id'=>$id])===1; }
}
