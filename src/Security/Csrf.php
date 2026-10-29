<?php
declare(strict_types=1);
namespace Elang\Security;
use Symfony\Component\HttpFoundation\Request;
final class Csrf
{
    public function token(Request $request): string
    {
        $session = $request->getSession();
        if (!$session->has('_csrf')) $session->set('_csrf', bin2hex(random_bytes(32)));
        return $session->get('_csrf');
    }
    public function verify(Request $request, string $token): bool
    {
        $stored = $request->getSession()->get('_csrf');
        return is_string($stored) && strlen($token) === 64 && hash_equals($stored, $token);
    }
}
