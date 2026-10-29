<?php
declare(strict_types=1);
namespace App\Controllers;
use Elang\Ai\DocumentationServer;
use Symfony\Component\HttpFoundation\{Request,Response};
final class McpController
{
    public function __construct(private DocumentationServer $server) {}
    public function __invoke(Request $request): Response { return $this->server->http($request); }
}
