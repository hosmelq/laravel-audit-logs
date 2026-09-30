<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Data;

final readonly class AuditLogChangesData
{
    /**
     * @param array<string, null|array<array-key, mixed>|bool|float|int|string> $before
     * @param array<string, null|array<array-key, mixed>|bool|float|int|string> $after
     */
    public function __construct(public array $before, public array $after)
    {
    }

    /**
     * @param array<string, null|array<array-key, mixed>|bool|float|int|string> $before
     * @param array<string, null|array<array-key, mixed>|bool|float|int|string> $after
     */
    public static function between(array $before, array $after): self
    {
        $previous = [];
        $current = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $attribute) {
            $hasBefore = array_key_exists($attribute, $before);
            $hasAfter = array_key_exists($attribute, $after);

            if ($hasBefore && $hasAfter && $before[$attribute] === $after[$attribute]) {
                continue;
            }

            if ($hasBefore) {
                $previous[$attribute] = $before[$attribute];
            }

            if ($hasAfter) {
                $current[$attribute] = $after[$attribute];
            }
        }

        return new self(before: $previous, after: $current);
    }

    /**
     * @return array{before: array<string, null|array<array-key, mixed>|bool|float|int|string>, after: array<string, null|array<array-key, mixed>|bool|float|int|string>}
     */
    public function toArray(): array
    {
        return ['before' => $this->before, 'after' => $this->after];
    }
}
