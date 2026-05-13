<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Contracts;

use HosmelQ\AuditLog\Data\AuditLogData;

interface AuditLogManager
{
    /**
     * Commit all pending audit logs to storage.
     */
    public function commit(): int;

    /**
     * Record an audit log for the next commit.
     */
    public function record(AuditLogData $log): void;

    /**
     * Record multiple audit logs for the next commit.
     *
     * @param iterable<AuditLogData> $logs
     */
    public function recordMany(iterable $logs): void;
}
