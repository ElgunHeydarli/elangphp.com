<?php
declare(strict_types=1);
namespace Elang\View;
use Elang\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Twig\Environment;
final class View
{
    public function __construct(private Environment $twig) {}
    public function render(string $template, array $data = [], int $status = 200): HttpResponse { return Response::html($this->twig->render($template.'.twig', $data), $status); }
    public function engine(): Environment { return $this->twig; }
}
