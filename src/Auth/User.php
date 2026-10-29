<?php
declare(strict_types=1);
namespace Elang\Auth;
final readonly class User implements \JsonSerializable
{
    public function __construct(public int $id, public string $name, public string $email, public string $role, public bool $verified, public int $authVersion, public bool $mustChangePassword) {}
    public static function fromRow(array $row): self { return new self((int)$row['id'], $row['name'], $row['email'], $row['role'], $row['verified_at'] !== null, (int)$row['auth_version'], (bool)$row['must_change_password']); }
    public function jsonSerialize(): array { return ['id'=>$this->id,'name'=>$this->name,'email'=>$this->email,'role'=>$this->role,'verified'=>$this->verified]; }
}
