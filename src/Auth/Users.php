<?php
declare(strict_types=1);
namespace Elang\Auth;
use Elang\Database\Database;
use Elang\Security\Password;
use Elang\Validation\{Validator, ValidationException};
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
final class Users
{
    public function __construct(private Database $database, private Password $passwords, private Validator $validator) {}
    public static function email(string $email): string { return mb_strtolower(trim($email)); }
    public function rowByEmail(string $email): array|false { return $this->database->connection()->fetchAssociative('SELECT * FROM auth_users WHERE email = ?', [self::email($email)]); }
    public function find(int $id): ?User { $row = $this->database->connection()->fetchAssociative('SELECT * FROM auth_users WHERE id = ?', [$id]); return $row ? User::fromRow($row) : null; }
    public function register(string $name, string $email, #[\SensitiveParameter] string $password): User { return $this->create($name,$email,$password); }
    public function create(string $name, string $email, #[\SensitiveParameter] string $password, string $role = 'user', bool $verified = false, bool $temporary = false): User
    {
        if (!in_array($role,['user','admin','editor'],true)) throw new \InvalidArgumentException('Invalid role.');
        $email=self::email($email); $name=trim($name);
        $this->validator->validate(['name'=>$name,'email'=>$email,'password'=>$password], [
            'name'=>[new Assert\NotBlank(),new Assert\Length(max:80)],
            'email'=>[new Assert\NotBlank(),new Assert\Email(),new Assert\Length(max:190)],
            'password'=>[new Assert\Length(min:$temporary?8:12,max:256)],
        ]);
        try { $this->database->connection()->insert('auth_users',['name'=>$name,'email'=>$email,'password'=>$this->passwords->hash($password),'role'=>$role,'verified_at'=>$verified?time():null,'auth_version'=>1,'must_change_password'=>$temporary?1:0,'created_at'=>time()]); }
        catch(UniqueConstraintViolationException) { throw new ValidationException(['email'=>['Registration could not be completed with those details. Try signing in or recovering your account.']]); }
        return $this->find((int)$this->database->connection()->lastInsertId()) ?? throw new \RuntimeException('User creation failed.');
    }
    public function changePassword(User $user, #[\SensitiveParameter] string $password): User
    {
        $this->validator->validate(['password'=>$password],['password'=>[new Assert\Length(min:12,max:256)]]);
        $connection=$this->database->connection();
        $connection->transactional(function() use($connection,$user,$password):void {
            $connection->executeStatement('UPDATE auth_users SET password = ?, auth_version = auth_version + 1, must_change_password = ? WHERE id = ?',[$this->passwords->hash($password),0,$user->id]);
            $connection->delete('auth_api_tokens',['user_id'=>$user->id]);
            $connection->delete('auth_tokens',['user_id'=>$user->id]);$connection->delete('auth_devices',['user_id'=>$user->id]);
        });
        return $this->find($user->id) ?? throw new \RuntimeException('User no longer exists.');
    }
    public function revokeCredentials(User $user):User
    {
        $connection=$this->database->connection();$connection->transactional(function()use($connection,$user):void {
            $connection->executeStatement('UPDATE auth_users SET auth_version = auth_version + 1 WHERE id = ?',[$user->id]);foreach(['auth_api_tokens','auth_tokens','auth_devices'] as $table) $connection->delete($table,['user_id'=>$user->id]);
        });return $this->find($user->id)??throw new \RuntimeException('User no longer exists.');
    }
    public function updateProfile(User $user,string $name): User
    {
        $name=trim($name);$this->validator->validate(['name'=>$name],['name'=>[new Assert\NotBlank(),new Assert\Length(max:80)]]);
        $this->database->connection()->update('auth_users',['name'=>$name],['id'=>$user->id]);
        return $this->find($user->id) ?? throw new \RuntimeException('User no longer exists.');
    }
}
