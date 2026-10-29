<?php
declare(strict_types=1);
namespace Elang\Http;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
final class Response
{
    public static function json(mixed $data, int $status = 200, array $headers = []): JsonResponse { return new JsonResponse($data, $status, $headers); }
    public static function html(string $html, int $status = 200): HttpResponse { return new HttpResponse($html, $status, ['Content-Type' => 'text/html; charset=utf-8']); }
    public static function redirect(string $path, int $status = 302): RedirectResponse
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\') || preg_match('/[\x00-\x20\x7f]/', $path)) throw new \InvalidArgumentException('Redirects must use a local absolute path.');
        return new RedirectResponse($path, $status);
    }
}
