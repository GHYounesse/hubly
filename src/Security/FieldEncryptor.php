<?php

namespace App\Security;

/**
 * Encrypts short secrets (hosting passwords, access notes) before they reach the database.
 *
 * Uses libsodium's authenticated encryption (XSalsa20-Poly1305): a stolen database dump is unreadable
 * without APP_ENCRYPTION_KEY, and a modified value is detected instead of silently decrypted.
 *
 * Stored format: "enc:v1:" + base64(nonce . ciphertext). Values without that prefix are legacy plain text
 * and are returned as they are, so the column can be migrated progressively (app:encryption:encrypt-existing).
 */
final class FieldEncryptor
{
    public const PREFIX = 'enc:v1:';

    private string $key;

    /** @param string $base64Key 32 random bytes, base64 encoded (see generateKey()) */
    public function __construct(string $base64Key)
    {
        $key = base64_decode($base64Key, true);

        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \InvalidArgumentException(sprintf(
                'APP_ENCRYPTION_KEY must be %d random bytes encoded in base64. Generate one with: php bin/console app:encryption:generate-key',
                SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            ));
        }

        $this->key = $key;
    }

    public static function generateKey(): string
    {
        return base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }

    public function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    public function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '' || $this->isEncrypted($plain)) {
            return $plain;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return self::PREFIX . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $this->key));
    }

    /**
     * @throws \RuntimeException when the value was encrypted with another key or has been tampered with
     */
    public function decrypt(?string $stored): ?string
    {
        if ($stored === null || !$this->isEncrypted($stored)) {
            return $stored; // empty or legacy plain text
        }

        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);

        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('The encrypted value is corrupted.');
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $this->key);

        if ($plain === false) {
            throw new \RuntimeException('Unable to decrypt the value: wrong APP_ENCRYPTION_KEY or tampered data.');
        }

        return $plain;
    }
}
