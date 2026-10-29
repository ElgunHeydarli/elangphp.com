<?php
declare(strict_types=1);
namespace Tests;
use Symfony\Component\HttpFoundation\Request;
use App\ContentRepository;
final class I18nTest extends TestCase
{
    private function site():\Elang\Application
    {
        $app=new \Elang\Application(dirname(__DIR__),['paths'=>['storage'=>$this->temporary.'/storage'],'url'=>'https://elangphp.com','database'=>['url'=>'sqlite:///:memory:']]);$app->provider(new \App\Providers\SiteProvider());$router=$app->router;require dirname(__DIR__).'/routes/web.php';return $app;
    }
    public function testAllThreeLanguagesHaveRealContentAndReciprocalCanonicals():void
    {
        $app=$this->site();$content=$app->container->get(ContentRepository::class);
        foreach(['en','az','ru'] as $locale) foreach($content->urls($locale) as $logical) {
            $path=$locale==='en'?$logical:'/'.$locale.($logical==='/'?'':$logical);$response=$app->handle(Request::create($path));self::assertSame(200,$response->getStatusCode(),$path);self::assertSame($locale,$response->headers->get('Content-Language'));self::assertFalse($response->headers->has('Set-Cookie'),$path);
            $dom=new \DOMDocument();@$dom->loadHTML($response->getContent());$xpath=new \DOMXPath($dom);self::assertSame($locale,$dom->documentElement->getAttribute('lang'));self::assertSame('https://elangphp.com'.$path,$xpath->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));self::assertSame(1,$dom->getElementsByTagName('h1')->length,$path);self::assertSame(4,$xpath->query('//link[@hreflang]')->length,$path);
            foreach($xpath->query('//a[starts-with(@href,"/")]') as $link) { $href=$link->getAttribute('href');if($link->hasAttribute('hreflang')) continue;if($locale!=='en'&&preg_match('~^/(docs|learn|guides|examples)(/|$)~',$href)) self::fail('Unlocalized link '.$href.' at '.$path); }
        }
    }
    public function testLanguageAwareSearchAndLocalizedErrorAndRedirect():void
    {
        $app=$this->site();$ru=$app->handle(Request::create('/api/search?lang=ru&q='.rawurlencode('маршрут')));$results=json_decode($ru->getContent(),true)['results'];self::assertNotEmpty($results);foreach($results as $result) self::assertStringStartsWith('/ru/',$result['url']);self::assertSame(400,$app->handle(Request::create('/api/search?lang=fr&q=routing'))->getStatusCode());
        self::assertSame('/az/docs/v0.1/introduction',$app->handle(Request::create('/az/docs'))->headers->get('Location'));$error=$app->handle(Request::create('/ru/not-a-page'));self::assertSame(404,$error->getStatusCode());self::assertSame('ru',$error->headers->get('Content-Language'));self::assertStringContainsString('lang="ru"',$error->getContent());
    }
    public function testLocalizedMcpDocumentsPointBackToTheirOwnLanguage():void
    {
        $app=$this->site();$payload=json_encode(['jsonrpc'=>'2.0','id'=>1,'method'=>'tools/call','params'=>['name'=>'read_doc','arguments'=>['path'=>'docs/v0.1/tenancy']]]);
        foreach(['az','ru'] as $language) { $request=Request::create('https://elangphp.com/'.$language.'/mcp','POST',[],[],[],['CONTENT_TYPE'=>'application/json'],$payload);$response=$app->handle($request);self::assertSame(200,$response->getStatusCode());$data=json_decode($response->getContent(),true);self::assertSame('https://elangphp.com/'.$language.'/docs/v0.1/tenancy',$data['result']['structuredContent']['url']); }
    }
    public function testTranslatedCodeExamplesStayExact():void
    {
        $root=dirname(__DIR__).'/content';foreach(['az','ru'] as $locale) foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/i18n/'.$locale,\FilesystemIterator::SKIP_DOTS)) as $file) {
            if($file->getExtension()!=='md') continue;$relative=substr($file->getPathname(),strlen($root.'/i18n/'.$locale.'/'));$english=file_get_contents($root.'/'.$relative);$translated=file_get_contents($file->getPathname());preg_match_all('/```[^\n]*\n[\s\S]*?```/',$english,$before);preg_match_all('/```[^\n]*\n[\s\S]*?```/',$translated,$after);self::assertSame($before[0],$after[0],$locale.'/'.$relative);
        }
    }
}
