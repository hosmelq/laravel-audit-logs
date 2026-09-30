<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogChangesData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use HosmelQ\AuditLog\Support\Config;

final class AuditLogRedactor
{
    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue> $metadata
     *
     * @return array<TKey, string|TValue>
     */
    public function metadata(array $metadata): array
    {
        $excluded = Config::redactionExclude();
        $masked = Config::redactionMask();

        foreach ($metadata as $key => $value) {
            if (in_array((string) $key, $excluded, true)) {
                unset($metadata[$key]);

                continue;
            }

            if (in_array((string) $key, $masked, true)) {
                $metadata[$key] = Config::redactionReplacement();
            }
        }

        return $metadata;
    }

    public function redact(AuditLogData $log): AuditLogData
    {
        return new AuditLogData(
            actor: new AuditLogActorData(
                id: $log->actor->id,
                metadata: $this->metadata($log->actor->metadata),
                name: $log->actor->name,
                type: $log->actor->type,
            ),
            bucket: $log->bucket,
            event: $log->event,
            source: $log->source,
            captureRequestMetadata: $log->captureRequestMetadata,
            changes: $this->changes($log->changes),
            correlationId: $log->correlationId,
            description: $log->description,
            id: $log->id,
            metadata: $this->metadata($log->metadata),
            occurredAt: $log->occurredAt,
            remoteIp: $log->captureRequestMetadata ? $log->remoteIp : null,
            targets: array_map(fn (AuditLogTargetData $target): AuditLogTargetData => new AuditLogTargetData(
                id: $target->id,
                metadata: $this->metadata($target->metadata),
                name: $target->name,
                type: $target->type,
            ), $log->targets),
            tenantId: $log->tenantId,
            userAgent: $log->captureRequestMetadata ? $log->userAgent : null,
        );
    }

    private function changes(null|AuditLogChangesData $changes): null|AuditLogChangesData
    {
        if (! $changes instanceof AuditLogChangesData) {
            return null;
        }

        $redacted = new AuditLogChangesData(
            before: $this->metadata($changes->before),
            after: $this->metadata($changes->after),
        );

        return $redacted->isEmpty() ? null : $redacted;
    }
}
