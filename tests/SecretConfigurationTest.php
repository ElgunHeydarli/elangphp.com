<?php
declare(strict_types=1);
namespace Tests;
final class SecretConfigurationTest extends TestCase
{
    public function testSecretFilesAreBoundedAndExplicitValuesTakePrecedence():void
    {
        $name='ELANG_TEST_PRIVATE_VALUE';$file=$this->temporary.'/secret';file_put_contents($file,"private fixture\n");$_ENV[$name.'_FILE']=$file;try { self::assertSame('private fixture',\Elang\Support\Config::secret($name));$_ENV[$name]='explicit value';self::assertSame('explicit value',\Elang\Support\Config::secret($name));unset($_ENV[$name]);file_put_contents($file,str_repeat('x',8193));try { \Elang\Support\Config::secret($name);self::fail('Oversized secret was accepted.'); }catch(\RuntimeException $e) { self::assertStringContainsString('invalid',$e->getMessage());self::assertStringNotContainsString(str_repeat('x',20),$e->getMessage()); } }finally { unset($_ENV[$name],$_ENV[$name.'_FILE']); }
    }
}
