<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests\TestSupport;

enum AuditLogBucket: string
{
    case Customer = 'customer_events';
}
