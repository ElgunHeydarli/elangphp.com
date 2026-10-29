<?php
declare(strict_types=1);
namespace App\Controllers;
use Elang\Http\{HttpException, Response};
use Elang\Security\Csrf;
use Elang\Support\Config;
use Elang\Validation\{Validator, ValidationException};
use Elang\View\View;
use Symfony\Component\HttpFoundation\{Request, Response as HttpResponse};
use Symfony\Component\Validator\Constraints as Assert;
final class TaskBoardController
{
    public function __construct(private View $view, private Csrf $csrf, private Validator $validator, private Config $config) {}
    private function base(): string { return $this->config->get('task_board.path', '/'); }
    private function render(Request $request, array $errors = [], string $input = '', int $status = 200): HttpResponse
    {
        $tasks = $request->getSession()->get('tasks', []);
        $response = $this->view->render('task-board', ['tasks' => $tasks, 'completed' => count(array_filter($tasks, fn ($task) => $task['done'])), 'errors' => $errors, 'input' => $input, 'token' => $this->csrf->token($request), 'base_path' => rtrim($this->base(), '/'), 'is_site_demo' => $this->base() !== '/'], $status);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, follow');
        return $response;
    }
    public function index(Request $request): HttpResponse { return $this->render($request); }
    public function store(Request $request): HttpResponse
    {
        $value = $request->request->all()['title'] ?? '';
        try { $valid = $this->validator->validate(['title' => is_string($value) ? trim($value) : $value], ['title' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 100)]]); }
        catch (ValidationException $error) { return $this->render($request, $error->errors['title'], is_string($value) ? $value : '', 422); }
        $tasks = $request->getSession()->get('tasks', []);
        if (count($tasks) >= 20) return $this->render($request, ['This learning example allows 20 tasks. Remove one before adding another.'], $valid['title'], 422);
        $id = bin2hex(random_bytes(8));
        $tasks[$id] = ['id' => $id, 'title' => $valid['title'], 'done' => false];
        $request->getSession()->set('tasks', $tasks);
        return Response::redirect($this->base(), 303);
    }
    public function toggle(Request $request, string $id): HttpResponse
    {
        $tasks = $request->getSession()->get('tasks', []);
        if (!isset($tasks[$id])) throw new HttpException(404, 'This task does not exist in your session.');
        $tasks[$id]['done'] = !$tasks[$id]['done'];
        $request->getSession()->set('tasks', $tasks);
        return Response::redirect($this->base(), 303);
    }
    public function delete(Request $request, string $id): HttpResponse
    {
        $tasks = $request->getSession()->get('tasks', []);
        if (!isset($tasks[$id])) throw new HttpException(404, 'This task does not exist in your session.');
        unset($tasks[$id]); $request->getSession()->set('tasks', $tasks);
        return Response::redirect($this->base(), 303);
    }
}
