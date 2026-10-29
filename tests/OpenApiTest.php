<?php
declare(strict_types=1);
namespace Tests;
final class OpenApiTest extends TestCase
{
    public function testGeneratedClientMatchesBothStarterContracts():void
    {
        $spec=require dirname(__DIR__).'/examples/api/resources/openapi.php';$source=(new \Elang\OpenApi\TypeScript())->generate($spec);foreach(['frontend-react/src/lib/generated.ts','frontend-next/lib/generated.ts'] as $path) self::assertSame($source,file_get_contents(dirname(__DIR__).'/examples/'.$path));self::assertStringContainsString('"createNote"',$source);self::assertStringContainsString('credentials: \'include\'',$source);
    }
    public function testUnsupportedSchemasFailInsteadOfInventingAType():void
    {
        $this->expectException(\InvalidArgumentException::class);(new \Elang\OpenApi\TypeScript())->generate(['openapi'=>'3.1.0','components'=>['schemas'=>['Unexpected'=>['allOf'=>[]]]]]);
    }
}
