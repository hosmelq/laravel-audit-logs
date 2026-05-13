<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Models\AuditLog as AuditLogModel;
use HosmelQ\AuditLog\Support\Config;
use Illuminate\Database\Connection;
use JsonException;

readonly class AuditLogWriter
{
    /**
     * @param list<AuditLogData> $logs
     *
     * @throws JsonException
     */
    public function write(array $logs): void
    {
        if ($logs === []) {
            return;
        }

        $model = $this->model();
        $connection = $model->getConnection();

        $connection->transaction(function () use ($connection, $logs, $model): void {
            foreach (array_chunk($logs, Config::insertChunkSize()) as $chunk) {
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
     *
     * @throws JsonException
     */
    private function insertChunk(Connection $connection, AuditLogModel $model, array $logs): void
    {
        $auditLogs = [];
        $insertedAt = new CarbonImmutable();

        foreach ($logs as $log) {
            $occurredAt = $log->occurredAt ?? $insertedAt;
            $expiresAt = $this->retentionExpiresAt($occurredAt);

            $auditLogs[] = [
                'actor_id' => $log->actor->id,
                'actor_name' => $log->actor->name,
                'actor_properties' => $this->json($log->actor->properties),
                'actor_type' => $log->actor->type,
                'batch_id' => $log->batchId,
                'bucket' => $log->bucket,
                'description' => $log->description,
                'event' => $log->event,
                'expires_at' => $expiresAt instanceof CarbonInterface ? $this->date($expiresAt) : null,
                'id' => $log->id ?? $model->newUniqueId(),
                'inserted_at' => $this->date($insertedAt),
                'occurred_at' => $this->date($occurredAt),
                'properties' => $this->json($log->properties),
                'remote_ip' => $log->remoteIp,
                'source' => $log->source,
                'targets' => $this->jsonArray($log->targets()),
                'tenant_id' => $log->tenantId,
                'user_agent' => $log->userAgent,
            ];
        }

        $connection->table($model->getTable())->insert($auditLogs);
    }

    /**
     * @param array<string, null|bool|float|int|string> $value
     *
     * @throws JsonException
     */
    private function json(array $value): string
    {
        return json_encode((object) $value, JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<array{type: string, id: string, name: null|string, properties: array<string, null|bool|float|int|string>}> $value
     *
     * @throws JsonException
     */
    private function jsonArray(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function model(): AuditLogModel
    {
        $model = DatabaseAuditLogManager::model();

        return new $model();
    }

    private function retentionExpiresAt(CarbonInterface $occurredAt): null|CarbonInterface
    {
        $days = Config::retentionDays();

        return $days === null ? null : $occurredAt->copy()->addDays($days);
    }
}
