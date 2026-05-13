<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests\TestSupport;

use HosmelQ\AuditLog\Models\AuditLog;
use Override;

class CustomAuditLog extends AuditLog
{
    #[Override]
    public function newUniqueId(): string
    {
        return '01J0000000000000000000000A';
    }
}
