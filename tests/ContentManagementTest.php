<?php
declare(strict_types=1);
namespace Tests;
use App\{ContentRepository,Cms\ContentStore};
use Elang\{Auth\Auth,Auth\Users,Database\Database};
use Symfony\Component\HttpFoundation\{Request,Session\Session,Session\Storage\MockArraySessionStorage};
final class ContentManagementTest extends TestCase
{
    private function site(): \Elang\Application
    {
        $app=new \Elang\Application(dirname(__DIR__),['environment'=>'testing','key'=>base64_encode(random_bytes(32)),'url'=>'https://elangphp.com','paths'=>['storage'=>$this->temporary.'/storage'],'database'=>['url'=>'sqlite:///:memory:']]);
        $app->container->get(\Elang\Database\Migrator::class)->migrate();$app->provider(new \App\Providers\SiteProvider());$router=$app->router;require dirname(__DIR__).'/routes/web.php';return $app;
    }
    private function article(string $title): array { return ['title'=>$title,'description'=>'A complete practical article with enough detail for a useful search description.','markdown'=>"# ".$title."\n\nA complete practical article.\n\n## Next step\n\n<script>alert(1)</script>\n\nA fenced example:\n\n```php\n# a comment\n```\n"]; }
    public function testDraftPublishRestoreAndPublicSourcesAgree(): void
    {
        $app=$this->site();$store=$app->container->get(ContentStore::class);$content=$app->container->get(ContentRepository::class);
        $store->save('docs/v0.1','publishing-test',$this->article('Publishable example'),0,1,'draft');
        self::assertSame(404,$app->handle(Request::create('/docs/v0.1/publishing-test'))->getStatusCode());self::assertSame([],$content->search('Publishable'));
        $store->save('docs/v0.1','publishing-test',$this->article('Publishable example'),1,1,'publish');
        $response=$app->handle(Request::create('/docs/v0.1/publishing-test'));self::assertSame(200,$response->getStatusCode());self::assertStringNotContainsString('<script>alert(1)</script>',$response->getContent());self::assertNotEmpty($content->search('Publishable'));self::assertContains('/docs/v0.1/publishing-test',$content->urls());
        $published=$content->document('docs/v0.1','publishing-test');$store->save('docs/v0.1','publishing-test',$this->article('Private revision'),2,1,'draft');
        self::assertSame($published['title'],$content->document('docs/v0.1','publishing-test')['title']);self::assertSame($published['modified'],$content->document('docs/v0.1','publishing-test')['modified']);self::assertSame([],$content->search('Private revision'));
        $restored=$store->restore('docs/v0.1','publishing-test',1,3,1);self::assertSame(4,$restored['revision']);self::assertCount(4,$store->history('docs/v0.1','publishing-test'));
        $source=$app->container->get(\Elang\Ai\DocumentSource::class);self::assertStringNotContainsString('Private revision',$source->read('docs/v0.1/publishing-test')['markdown']);
        self::assertSame(['en'],$content->languages('/docs/v0.1/publishing-test'));
    }
    public function testTranslationsAreSeparateAndConcurrentEditsConflict(): void
    {
        $store=$this->site()->container->get(ContentStore::class);$store->save('updates','release-note',$this->article('English release'),0,1,'publish','en');$store->save('updates','release-note',$this->article('Azərbaycan yeniliyi'),0,1,'publish','az');
        self::assertSame('English release',$store->publishedPage('updates','release-note','en')['title']);self::assertSame('Azərbaycan yeniliyi',$store->publishedPage('updates','release-note','az')['title']);self::assertNull($store->publishedPage('updates','release-note','ru'));
        $this->expectException(\Elang\Http\HttpException::class);$this->expectExceptionMessage('another session');$store->save('updates','release-note',$this->article('Conflicting revision'),0,1,'publish');
    }
    public function testAdminRequiresVerifiedAdministratorAndCsrf(): void
    {
        $app=$this->site();$session=new Session(new MockArraySessionStorage());$request=function(string $path,string $method='GET',array $input=[])use($session):Request { $request=Request::create($path,$method,$input);$request->setSession($session);return $request; };
        self::assertSame('/login',$app->handle($request('/admin'))->headers->get('Location'));
        $users=$app->container->get(Users::class);$auth=$app->container->get(Auth::class);$member=$users->create('Member','member@example.com','Long unique passphrase 123','user',true);$auth->login($request('/'),$member);self::assertSame(403,$app->handle($request('/admin'))->getStatusCode());
        $admin=$users->create('Admin','admin@example.com','Another unique passphrase 456','admin',true);$auth->login($request('/'),$admin);$page=$app->handle($request('/az/admin'));self::assertSame(200,$page->getStatusCode(),$page->getContent());self::assertStringContainsString('no-store',$page->headers->get('Cache-Control'));self::assertSame(419,$app->handle($request('/admin/save','POST'))->getStatusCode());
        self::assertSame(200,$app->handle($request('/admin/edit?locale=ru&section=docs/v0.1&slug=routing'))->getStatusCode());
    }
    public function testPublishedUpdatesReachTheirLocalizedFeedWhileDraftsStayPrivate():void
    {
        $app=$this->site();$store=$app->container->get(ContentStore::class);$store->save('updates','release-note',$this->article('English release'),0,1,'publish','en');$store->save('updates','release-note',$this->article('Русский выпуск'),0,1,'publish','ru');$store->save('updates','private-note',$this->article('Private unpublished release'),0,1,'draft','en');
        $english=$app->handle(Request::create('/feed.xml'));$russian=$app->handle(Request::create('/ru/feed.xml'));self::assertStringContainsString('English release',$english->getContent());self::assertStringNotContainsString('Русский выпуск',$english->getContent());self::assertStringNotContainsString('Private unpublished release',$english->getContent());self::assertStringContainsString('Русский выпуск',$russian->getContent());self::assertStringContainsString('https://elangphp.com/ru/updates/release-note',$russian->getContent());$dom=new \DOMDocument();self::assertTrue($dom->loadXML($russian->getContent()));
    }

}
