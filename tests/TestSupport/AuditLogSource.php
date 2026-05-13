<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests\TestSupport;

enum AuditLogSource: string
{
    case Customer = 'customer';
}
