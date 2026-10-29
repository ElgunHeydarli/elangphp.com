<?php
declare(strict_types=1);
namespace Elang\Auth;
final readonly class AccessToken
{
    public function __construct(public int $id,public User $user,private array $scopes) {}
    public function allows(string $scope): bool { return in_array($scope,$this->scopes,true); }
}
