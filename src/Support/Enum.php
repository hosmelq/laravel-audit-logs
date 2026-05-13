<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Support;

use BackedEnum;

class Enum
{
    public static function value(BackedEnum|string $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return $value;
    }
}
