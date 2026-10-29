<?php
declare(strict_types=1);
namespace Elang\Auth;
final class Gate
{
    private array $policies=[];
    public function define(string $ability,callable $policy): void { $this->policies[$ability]=$policy; }
    public function allows(string $ability,?User $user,mixed ...$arguments): bool { return $user!==null&&isset($this->policies[$ability])&&($this->policies[$ability])($user,...$arguments)===true; }
    public function authorize(string $ability,?User $user,mixed ...$arguments): void { if(!$this->allows($ability,$user,...$arguments)) throw new \Elang\Http\HttpException(403,'You are not allowed to perform this action.'); }
}
