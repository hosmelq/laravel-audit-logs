<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Contracts;

use Closure;
use HosmelQ\AuditLog\Data\AuditLogData;

interface AuditLogManager
{
    /**
     * @template TReturn
     *
     * @param Closure(): TReturn $callback
     *
     * @return TReturn
     */
    public function correlate(Closure $callback, null|string $id = null): mixed;

    /**
     * @param AuditLogData|iterable<AuditLogData> $logs
     */
    public function record(AuditLogData|iterable $logs): void;
}
