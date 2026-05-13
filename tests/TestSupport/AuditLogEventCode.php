<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests\TestSupport;

enum AuditLogEventCode: int
{
    case DocumentPublished = 1001;
}
