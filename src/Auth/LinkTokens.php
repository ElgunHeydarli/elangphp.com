<?php
declare(strict_types=1);
namespace Elang\Auth;
use Elang\Database\Database;
final class LinkTokens
{
    public function __construct(private Database $database) {}
    public function issue(User $user,string $purpose,int $ttl=3600): string
    {
        if(!in_array($purpose,['reset','verify'],true)||$ttl<1||$ttl>86400) throw new \InvalidArgumentException('Invalid access-link purpose or lifetime.');
        $token=bin2hex(random_bytes(32));$connection=$this->database->connection();
        $connection->transactional(function() use($connection,$user,$purpose,$ttl,$token):void {
            $connection->executeStatement('DELETE FROM auth_tokens WHERE user_id = ? AND purpose = ?',[$user->id,$purpose]);
            $connection->insert('auth_tokens',['token_hash'=>hash('sha256',$token),'user_id'=>$user->id,'purpose'=>$purpose,'expires_at'=>time()+$ttl]);
        });
        return $token;
    }
    public function consume(string $token,string $purpose): ?int
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$token)) return null;
        return $this->database->connection()->transactional(function($connection) use($token,$purpose):?int {
            $hash=hash('sha256',$token);
            $row=$connection->fetchAssociative('SELECT user_id FROM auth_tokens WHERE token_hash = ? AND purpose = ? AND expires_at > ?',[$hash,$purpose,time()]);
            if(!$row) return null;
            $count=$connection->executeStatement('DELETE FROM auth_tokens WHERE token_hash = ? AND purpose = ? AND expires_at > ?',[$hash,$purpose,time()]);
            return $count===1?(int)$row['user_id']:null;
        });
    }
}
