<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Data;

use InvalidArgumentException;

final readonly class AuditLogRetentionData
{
    public function __construct(public null|int $days)
    {
        if ($days !== null && $days < 0) {
            throw new InvalidArgumentException('Audit log retention days must be a non-negative integer or null.');
        }
    }
}
