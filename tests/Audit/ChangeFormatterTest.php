<?php

namespace App\Tests\Audit;

use App\Entity\Societe;
use App\Service\Audit\ChangeFormatter;
use PHPUnit\Framework\TestCase;

class ChangeFormatterTest extends TestCase
{
    private ChangeFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ChangeFormatter();
    }

    public function testKeepsBeforeAndAfterOfChangedFields(): void
    {
        $changes = $this->formatter->format([
            'status' => ['à faire', 'en cours'],
            'projetValide' => [false, true],
        ]);

        $this->assertSame([
            'status' => ['old' => 'à faire', 'new' => 'en cours'],
            'projetValide' => ['old' => false, 'new' => true],
        ], $changes);
    }

    public function testDropsTimestampsAndSecrets(): void
    {
        $changes = $this->formatter->format([
            'updated' => [new \DateTime('-1 day'), new \DateTime()],
            'password' => ['old-hash', 'new-hash'],
            'twoFactorCode' => [null, '123456'],
            'twoFactorExpiresAt' => [null, new \DateTimeImmutable('+5 minutes')],
            'nom' => ['Tazi', 'Tazi Alaoui'],
        ]);

        $this->assertSame(['nom'], array_keys($changes));
    }

    public function testIgnoresFieldsWhoseNormalizedValueDidNotChange(): void
    {
        $same = new \DateTime('2026-11-14 00:00:00');

        $changes = $this->formatter->format([
            'dateExpiration' => [$same, new \DateTimeImmutable('2026-11-14 00:00:00')],
        ]);

        $this->assertSame([], $changes);
    }

    public function testDatesAreReadable(): void
    {
        $this->assertSame('2026-11-14', $this->formatter->normalize(new \DateTime('2026-11-14')));
        $this->assertSame('2026-11-14 09:30', $this->formatter->normalize(new \DateTime('2026-11-14 09:30:45')));
    }

    public function testRelatedEntitiesAreReducedToTheirName(): void
    {
        $societe = (new Societe())->setDenomination('Atlas Immobilier');

        $this->assertSame('Atlas Immobilier', $this->formatter->normalize($societe));
    }

    public function testNullAndEmptyValuesAreKept(): void
    {
        $changes = $this->formatter->format(['remarques' => [null, 'À relancer']]);

        $this->assertNull($changes['remarques']['old']);
        $this->assertSame('À relancer', $changes['remarques']['new']);
    }

    public function testLongTextIsTruncated(): void
    {
        $value = $this->formatter->normalize(str_repeat('a', 1000));

        $this->assertSame(300, mb_strlen((string) $value));
        $this->assertStringEndsWith('…', (string) $value);
    }

    public function testArraysBecomeJsonText(): void
    {
        $this->assertSame('["ROLE_ADMIN"]', $this->formatter->normalize(['ROLE_ADMIN']));
    }
}
