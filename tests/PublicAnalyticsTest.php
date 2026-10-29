<?php
declare(strict_types=1);
namespace Tests;
use Symfony\Component\HttpFoundation\Request;
final class PublicAnalyticsTest extends TestCase
{
    private function site(array $tracking=[]): \Elang\Application
    {
        $app=new \Elang\Application(dirname(__DIR__),['paths'=>['storage'=>$this->temporary.'/storage'],'url'=>'https://elangphp.com','database'=>['url'=>'sqlite:///:memory:'],'site_analytics'=>$tracking]);
        $app->provider(new \App\Providers\SiteProvider());$router=$app->router;require dirname(__DIR__).'/routes/web.php';return $app;
    }
    public function testPublicPagesExposeValidatedIdentifiersAndScopedPolicy(): void
    {
        $app=$this->site(['measurement_id'=>'G-TEST123456','verification'=>str_repeat('a',43)]);
        foreach(['/','/az','/ru','/docs/v0.1/installation','/az/privacy','/ru/privacy'] as $path) {
            $response=$app->handle(Request::create($path.'?email=private@example.test'));
            self::assertSame(200,$response->getStatusCode(),$path);
            self::assertStringContainsString('data-analytics-id="G-TEST123456"',$response->getContent());
            self::assertStringContainsString('name="google-site-verification"',$response->getContent());
            self::assertStringContainsString('data-analytics-panel',$response->getContent());
            self::assertStringNotContainsString('private@example.test',$response->getContent());
            self::assertStringContainsString('https://www.googletagmanager.com',$response->headers->get('Content-Security-Policy'));
            self::assertStringNotContainsString('unsafe-inline',$response->headers->get('Content-Security-Policy'));
        }
        foreach(['/login','/admin','/az/admin/integrations','/api/health','/not-found','/demo/task-board'] as $path) {
            $response=$app->handle(Request::create($path));
            self::assertStringNotContainsString('data-analytics-id',$response->getContent());
            self::assertStringNotContainsString('googletagmanager.com',$response->headers->get('Content-Security-Policy'));
        }
    }
    public function testDisabledOrMalformedSettingsKeepStrictPolicyAndNoTracking(): void
    {
        foreach([[],['measurement_id'=>'G-BAD" onload="alert(1)','verification'=>'<script>']] as $settings) {
            $response=$this->site($settings)->handle(Request::create('/'));
            self::assertStringNotContainsString('data-analytics-id',$response->getContent());
            self::assertStringNotContainsString('google-site-verification',$response->getContent());
            self::assertStringNotContainsString('googletagmanager.com',$response->headers->get('Content-Security-Policy'));
        }
    }
    public function testFrameworkKeepsAnExplicitApplicationPolicyWithoutChangingTheDefault(): void
    {
        $app=$this->application();$policy="default-src 'none'; frame-ancestors 'none'";
        $app->router->get('/policy',fn()=>new \Symfony\Component\HttpFoundation\Response('ok',200,['Content-Security-Policy'=>$policy]));
        self::assertSame($policy,$app->handle(Request::create('/policy'))->headers->get('Content-Security-Policy'));
        self::assertStringContainsString("script-src 'self'",$app->handle(Request::create('/missing'))->headers->get('Content-Security-Policy'));
    }
}
