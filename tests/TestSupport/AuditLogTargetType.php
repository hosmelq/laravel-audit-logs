<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests\TestSupport;

enum AuditLogTargetType: string
{
    case Document = 'document';
}
