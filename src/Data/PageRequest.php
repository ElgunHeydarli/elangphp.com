<?php
declare(strict_types=1);
namespace Elang\Data;
final class PageRequest
{
    public static function number(mixed $value): int
    {
        if(!is_int($value)&&!is_string($value)) throw new \Elang\Http\HttpException(400,'Page must be a positive integer.');
        $page=filter_var($value,FILTER_VALIDATE_INT);
        if($page===false||$page<1||$page>1000000) throw new \Elang\Http\HttpException(400,'Page must be between 1 and 1000000.');
        return $page;
    }
}
