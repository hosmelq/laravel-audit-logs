<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Commands;

use HosmelQ\AuditLog\Contracts\AuditLogManager;
use HosmelQ\AuditLog\Support\Config;
use Illuminate\Console\Command;

class CommitAuditLogs extends Command
{
    protected $description = 'Commit pending audit logs';

    protected $signature = 'audit-log:commit';

    public function handle(AuditLogManager $logger): int
    {
        $count = $logger->commit();

        if ($count === 0) {
            $this->info('No audit logs to commit.');
        } elseif (Config::queue()) {
            $this->info(sprintf('Queued %s audit log(s) for commit.', $count));
        } else {
            $this->info(sprintf('Committed %s audit log(s).', $count));
        }

        return self::SUCCESS;
    }
}
