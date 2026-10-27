<?php

namespace App\Doctrine\Type;

use App\Security\FieldEncryptor;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

/**
 * A TEXT column whose content is encrypted transparently: entities hold plain text,
 * the database holds ciphertext. Registered as "encrypted_text" in config/packages/doctrine.yaml.
 */
class EncryptedTextType extends TextType
{
    public const NAME = 'encrypted_text';

    private static ?\Closure $provider = null;
    private static ?FieldEncryptor $encryptor = null;

    /**
     * Doctrine types are created outside the container, so the Kernel hands them a lazy provider
     * (the key is only read the first time a value is encrypted or decrypted).
     */
    public static function setEncryptorProvider(\Closure $provider): void
    {
        self::$provider = $provider;
        self::$encryptor = null;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : self::encryptor()->encrypt((string) $value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : self::encryptor()->decrypt((string) $value);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    private static function encryptor(): FieldEncryptor
    {
        if (self::$encryptor === null) {
            if (self::$provider === null) {
                throw new \LogicException('The field encryptor is not configured: boot the Kernel first.');
            }
            self::$encryptor = (self::$provider)();
        }

        return self::$encryptor;
    }
}
