<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Contracts;

use HosmelQ\AuditLog\Data\AuditLogData;

interface AuditLogRepository
{
    /**
     * Add an audit log to the pending buffer.
     */
    public function add(AuditLogData $log): void;

    /**
     * Return all pending audit logs.
     *
     * @return list<AuditLogData>
     */
    public function all(): array;

    /**
     * Flush all pending audit logs.
     */
    public function flush(): void;
}
