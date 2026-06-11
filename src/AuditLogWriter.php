<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Models\AuditLog as AuditLogModel;
use HosmelQ\AuditLog\Support\Config;
use HosmelQ\AuditLog\Support\RequestMetadata;
use Illuminate\Database\Connection;

final readonly class AuditLogWriter
{
    public function __construct(private RequestMetadata $requestMetadata)
    {
    }

    /**
     * @param list<AuditLogData> $logs
     */
    public function write(array $logs): void
    {
        if ($logs === []) {
            return;
        }

        $model = new (DatabaseAuditLogManager::model());
        $connection = $model->getConnection();

        $connection->transaction(function () use ($connection, $logs, $model): void {
            foreach (array_chunk($logs, Config::storageInsertChunkSize()) as $chunk) {
                $this->insertChunk($connection, $model, $chunk);
            }
        });
    }

    private function date(CarbonInterface $date): string
    {
        return CarbonImmutable::createFromInterface($date)->format('Y-m-d H:i:s.v');
    }

    /**
     * @param list<AuditLogData> $logs
     */
    private function insertChunk(Connection $connection, AuditLogModel $model, array $logs): void
    {
        $auditLogs = [];
        $insertedAt = new CarbonImmutable();
        $retentionDays = Config::retentionDays();

        foreach ($logs as $log) {
            $occurredAt = $log->occurredAt;
            $expiresAt = $retentionDays === null ? null : $occurredAt->addDays($retentionDays);
            $targets = $log->targets();

            foreach ($targets as $index => $target) {
                $targets[$index]['metadata'] = (object) $target['metadata'];
            }

            $auditLogs[] = [
                'actor_id' => $log->actor->id,
                'actor_metadata' => json_encode((object) $log->actor->metadata, JSON_THROW_ON_ERROR),
                'actor_name' => $log->actor->name,
                'actor_type' => $log->actor->type,
                'bucket' => $log->bucket,
                'correlation_id' => $log->correlationId,
                'description' => $log->description,
                'event' => $log->event,
                'expires_at' => $expiresAt instanceof CarbonInterface ? $this->date($expiresAt) : null,
                'id' => $log->id,
                'inserted_at' => $this->date($insertedAt),
                'metadata' => json_encode((object) $log->metadata, JSON_THROW_ON_ERROR),
                'occurred_at' => $this->date($occurredAt),
                'remote_ip' => $log->remoteIp ?? $this->requestMetadata->remoteIp(),
                'source' => $log->source,
                'targets' => json_encode($targets, JSON_THROW_ON_ERROR),
                'tenant_id' => $log->tenantId,
                'user_agent' => $log->userAgent ?? $this->requestMetadata->userAgent(),
            ];
        }

        $connection->table($model->getTable())->insert($auditLogs);
    }
}
