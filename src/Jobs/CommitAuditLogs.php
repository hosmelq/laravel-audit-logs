<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Jobs;

use HosmelQ\AuditLog\AuditLogWriter;
use HosmelQ\AuditLog\Data\AuditLogData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CommitAuditLogs implements ShouldQueue
{
    use Queueable;

    /**
     * @param list<AuditLogData> $logs
     */
    public function __construct(public array $logs)
    {
    }

    public function handle(AuditLogWriter $writer): void
    {
        $writer->write($this->logs);
    }
}
