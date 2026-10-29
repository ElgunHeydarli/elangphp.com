<?php
declare(strict_types=1);
namespace Elang\Security;
final class Encryption
{
    private string $key;
    public function __construct(string $key)
    {
        $decoded = base64_decode($key, true);
        if ($decoded === false || strlen($decoded) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) throw new \InvalidArgumentException('APP_KEY must be a base64-encoded 32-byte key. Run php elang key:generate.');
        $this->key = $decoded;
    }
    public function encrypt(string $value): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce.sodium_crypto_secretbox($value, $nonce, $this->key));
    }
    public function decrypt(string $value): string
    {
        $raw = base64_decode($value, true);
        if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) throw new \UnexpectedValueException('Invalid encrypted payload.');
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $this->key);
        if ($plain === false) throw new \UnexpectedValueException('Encrypted payload authentication failed.');
        return $plain;
    }
}
