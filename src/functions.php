<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use BackedEnum;
use HosmelQ\AuditLog\Contracts\AuditLogManager;

/**
 * @return ($event is null ? AuditLogManager : PendingAuditLog)
 */
function audit_log(null|BackedEnum|string $event = null): AuditLogManager|PendingAuditLog
{
    if ($event === null) {
        return resolve(AuditLogManager::class);
    }

    return PendingAuditLog::make($event);
}
