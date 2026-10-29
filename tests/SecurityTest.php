<?php
declare(strict_types=1);
namespace Tests;
use Elang\Security\{Encryption, Password, Csrf};
use Elang\Http\Middleware\CsrfMiddleware;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
final class SecurityTest extends TestCase
{
    public function testEncryptionAuthenticatesAndUsesUniqueNonces(): void
    {
        $encryption = new Encryption(base64_encode(random_bytes(32)));
        $first = $encryption->encrypt('private'); $second = $encryption->encrypt('private');
        self::assertNotSame($first, $second); self::assertSame('private', $encryption->decrypt($first));
        $raw = base64_decode($first); $raw[30] = chr(ord($raw[30]) ^ 1);
        $this->expectException(\UnexpectedValueException::class); $encryption->decrypt(base64_encode($raw));
    }
    public function testInvalidEncryptionKeyFailsEarly(): void { $this->expectException(\InvalidArgumentException::class); new Encryption('bad-key'); }
    public function testMalformedCiphertextIsRejected(): void { $this->expectException(\UnexpectedValueException::class); (new Encryption(base64_encode(random_bytes(32))))->decrypt('not base64'); }
    public function testPasswordHashAndVerification(): void
    {
        $passwords = new Password(); $hash = $passwords->hash('correct horse battery staple');
        self::assertTrue($passwords->verify('correct horse battery staple', $hash)); self::assertFalse($passwords->verify('wrong', $hash)); self::assertFalse($passwords->needsRehash($hash));
    }
    public function testCsrfRoundTripAndMissingTokenRejection(): void
    {
        $request = Request::create('/form', 'POST'); $request->setSession(new Session(new MockArraySessionStorage()));
        $csrf = new Csrf(); $token = $csrf->token($request); self::assertSame($token, $csrf->token($request)); self::assertTrue($csrf->verify($request, $token));
        $middleware = new CsrfMiddleware($csrf);
        $request->headers->set('X-CSRF-Token', $token); self::assertSame(200, $middleware->process($request, fn () => new Response('ok'))->getStatusCode());
        $request->headers->remove('X-CSRF-Token'); $this->expectException(\Elang\Http\HttpException::class); $middleware->process($request, fn () => new Response('ok'));
    }
    public function testCsrfSafeMethodsDoNotRequireSession(): void
    {
        self::assertSame(200, (new CsrfMiddleware(new Csrf()))->process(Request::create('/'), fn () => new Response('ok'))->getStatusCode());
    }
}
