<?php

namespace App\Tests\Security;

use App\Security\FieldEncryptor;
use PHPUnit\Framework\TestCase;

class FieldEncryptorTest extends TestCase
{
    private FieldEncryptor $encryptor;

    protected function setUp(): void
    {
        $this->encryptor = new FieldEncryptor(FieldEncryptor::generateKey());
    }

    public function testEncryptedValuesAreNotReadableAndRoundTrip(): void
    {
        $secret = "ftp://user:p@ssw0rd@ftp.example.test\nSSH: root / Tr0ub4dor";

        $stored = $this->encryptor->encrypt($secret);

        $this->assertNotSame($secret, $stored);
        $this->assertStringStartsWith(FieldEncryptor::PREFIX, (string) $stored);
        $this->assertStringNotContainsString('p@ssw0rd', (string) $stored);
        $this->assertSame($secret, $this->encryptor->decrypt($stored));
    }

    public function testTheSameValueIsEncryptedDifferentlyEachTime(): void
    {
        $this->assertNotSame($this->encryptor->encrypt('secret'), $this->encryptor->encrypt('secret'));
    }

    public function testNullAndEmptyValuesPassThrough(): void
    {
        $this->assertNull($this->encryptor->encrypt(null));
        $this->assertSame('', $this->encryptor->encrypt(''));
        $this->assertNull($this->encryptor->decrypt(null));
    }

    public function testAlreadyEncryptedValuesAreNotEncryptedTwice(): void
    {
        $stored = $this->encryptor->encrypt('secret');

        $this->assertSame($stored, $this->encryptor->encrypt($stored));
    }

    public function testLegacyPlainTextIsReturnedAsIs(): void
    {
        $this->assertSame('stored before encryption existed', $this->encryptor->decrypt('stored before encryption existed'));
    }

    public function testAnotherKeyCannotReadTheValue(): void
    {
        $stored = $this->encryptor->encrypt('secret');

        $this->expectException(\RuntimeException::class);
        (new FieldEncryptor(FieldEncryptor::generateKey()))->decrypt($stored);
    }

    public function testTamperedDataIsRejected(): void
    {
        $stored = (string) $this->encryptor->encrypt('secret');
        $tampered = substr($stored, 0, -4) . 'AAAA';

        $this->expectException(\RuntimeException::class);
        $this->encryptor->decrypt($tampered);
    }

    public function testAnInvalidKeyIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FieldEncryptor('not-a-valid-key');
    }
}
