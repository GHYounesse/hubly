<?php

namespace App\Service\Audit;

/**
 * Turns a Doctrine change set into the compact "before / after" structure stored in Historique::$changes.
 *
 * Technical and sensitive fields are dropped, values are reduced to scalars so they can be stored as JSON.
 */
final class ChangeFormatter
{
    /** Never recorded: timestamps are noise, credentials are secrets. */
    public const IGNORED_FIELDS = [
        'created', 'updated', 'createdAt', 'updatedAt',
        'password', 'twoFactorCode', 'twoFactorExpiresAt',
    ];

    private const MAX_LENGTH = 300;

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changeSet as returned by UnitOfWork::getEntityChangeSet()
     *
     * @return array<string, array{old: scalar|null, new: scalar|null}>
     */
    public function format(array $changeSet): array
    {
        $changes = [];

        foreach ($changeSet as $field => $values) {
            if (in_array($field, self::IGNORED_FIELDS, true)) {
                continue;
            }

            $old = $this->normalize($values[0] ?? null);
            $new = $this->normalize($values[1] ?? null);

            if ($old === $new) {
                continue;
            }

            $changes[$field] = ['old' => $old, 'new' => $new];
        }

        return $changes;
    }

    /**
     * Reduces any value to something a JSON column can hold and a human can read.
     */
    public function normalize(mixed $value): string|int|float|bool|null
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            return $this->truncate($value);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s') === '00:00:00' ? $value->format('Y-m-d') : $value->format('Y-m-d H:i');
        }

        if (is_array($value)) {
            return $this->truncate((string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if (is_object($value)) {
            return $this->describeEntity($value);
        }

        return $this->truncate(get_debug_type($value));
    }

    /** "Atlas Immobilier", or "Societe #3" when the object has no readable name. */
    private function describeEntity(object $entity): string
    {
        foreach (['getNom', 'getDenomination', 'getSite', 'getEmail'] as $getter) {
            if (method_exists($entity, $getter)) {
                $label = $entity->{$getter}();
                if (is_string($label) && $label !== '') {
                    return $this->truncate($label);
                }
            }
        }

        $short = (new \ReflectionClass($entity))->getShortName();
        $id = method_exists($entity, 'getId') ? $entity->getId() : null;

        return $id !== null ? sprintf('%s #%s', $short, $id) : $short;
    }

    private function truncate(string $value): string
    {
        return mb_strlen($value) > self::MAX_LENGTH ? mb_substr($value, 0, self::MAX_LENGTH - 1) . '…' : $value;
    }
}
