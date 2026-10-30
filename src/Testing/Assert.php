<?php
declare(strict_types=1);
namespace Elang\Testing;
/** Optional PHPUnit integration keeps fluent assertions visible to the test runner. */
final class Assert
{
    public static function same(mixed $expected,mixed $actual,string $message):void
    {
        if(class_exists(\PHPUnit\Framework\Assert::class)){\PHPUnit\Framework\Assert::assertSame($expected,$actual,$message);return;}
        if($expected!==$actual)throw new \AssertionError($message);
    }
    public static function truth(bool $condition,string $message):void { self::same(true,$condition,$message); }
}
