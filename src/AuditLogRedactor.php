<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use HosmelQ\AuditLog\Support\Config;

final class AuditLogRedactor
{
    /**
     * @param array<string, null|bool|float|int|string> $metadata
     *
     * @return array<string, null|bool|float|int|string>
     */
    public function metadata(array $metadata): array
    {
        $excluded = Config::redactionExclude();
        $masked = Config::redactionMask();

        foreach ($metadata as $key => $value) {
            if (in_array($key, $excluded, true)) {
                unset($metadata[$key]);

                continue;
            }

            if (in_array($key, $masked, true)) {
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
}
