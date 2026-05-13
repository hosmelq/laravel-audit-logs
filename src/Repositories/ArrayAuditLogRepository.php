<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Repositories;

use HosmelQ\AuditLog\Contracts\AuditLogRepository;
use HosmelQ\AuditLog\Data\AuditLogData;

class ArrayAuditLogRepository implements AuditLogRepository
{
    /**
     * @var list<AuditLogData>
     */
    private array $logs = [];

    public function add(AuditLogData $log): void
    {
        $this->logs[] = $log;
    }

    /**
     * @return list<AuditLogData>
     */
    public function all(): array
    {
        return $this->logs;
    }

    public function flush(): void
    {
        $this->logs = [];
    }
}
