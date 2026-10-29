<?php
declare(strict_types=1);
namespace Tests;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
final class TaskBoardTest extends TestCase
{
    private function site(): \Elang\Application
    {
        $app = new \Elang\Application(dirname(__DIR__), ['paths' => ['storage' => $this->temporary.'/storage'], 'task_board' => ['path' => '/demo/task-board'], 'database' => ['url' => 'sqlite:///:memory:']]);
        $app->provider(new \App\Providers\SiteProvider()); $router = $app->router; require dirname(__DIR__).'/routes/web.php'; return $app;
    }
    private function request(string $path, Session $session, string $method = 'GET', array $data = []): Request
    {
        $request = Request::create($path, $method, $data); $request->setSession($session); return $request;
    }
    public function testCreateToggleDeleteAndIsolation(): void
    {
        $app = $this->site(); $session = new Session(new MockArraySessionStorage());
        self::assertSame(200, $app->handle($this->request('/demo/task-board', $session))->getStatusCode());
        $token = $session->get('_csrf');
        $created = $app->handle($this->request('/demo/task-board/add', $session, 'POST', ['_token' => $token, 'title' => 'Read the routing guide']));
        self::assertSame(303, $created->getStatusCode()); self::assertSame('/demo/task-board', $created->headers->get('Location'));
        $id = array_key_first($session->get('tasks'));
        self::assertSame(303, $app->handle($this->request('/demo/task-board/toggle/'.$id, $session, 'POST', ['_token' => $token]))->getStatusCode());
        self::assertTrue($session->get('tasks')[$id]['done']);
        $other = new Session(new MockArraySessionStorage()); $otherToken = (new \Elang\Security\Csrf())->token($this->request('/', $other));
        self::assertSame(404, $app->handle($this->request('/demo/task-board/delete/'.$id, $other, 'POST', ['_token' => $otherToken]))->getStatusCode());
        self::assertCount(1, $session->get('tasks'));
        self::assertSame(303, $app->handle($this->request('/demo/task-board/delete/'.$id, $session, 'POST', ['_token' => $token]))->getStatusCode()); self::assertSame([], $session->get('tasks'));
    }
    public function testCsrfValidationAndOutputEscaping(): void
    {
        $app = $this->site(); $session = new Session(new MockArraySessionStorage()); $request = $this->request('/', $session); $token = (new \Elang\Security\Csrf())->token($request);
        self::assertSame(419, $app->handle($this->request('/demo/task-board/add', $session, 'POST', ['title' => 'without token']))->getStatusCode());
        $invalid = $app->handle($this->request('/demo/task-board/add', $session, 'POST', ['_token' => $token, 'title' => '   ']));
        self::assertSame(422, $invalid->getStatusCode()); self::assertStringContainsString('role="alert"', $invalid->getContent());
        $app->handle($this->request('/demo/task-board/add', $session, 'POST', ['_token' => $token, 'title' => '<script>alert(1)</script>']));
        $page = $app->handle($this->request('/demo/task-board', $session));
        self::assertStringContainsString('&lt;script&gt;', $page->getContent()); self::assertStringNotContainsString('<script>alert', $page->getContent());
        self::assertSame('noindex, follow', $page->headers->get('X-Robots-Tag')); self::assertStringContainsString('no-store', $page->headers->get('Cache-Control'));
    }
}
