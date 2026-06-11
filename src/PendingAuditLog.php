<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use BackedEnum;
use Carbon\CarbonInterface;
use HosmelQ\AuditLog\Contracts\AuditLogManager;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use HosmelQ\AuditLog\Support\Config;
use HosmelQ\AuditLog\Support\Enum;
use HosmelQ\AuditLog\Support\RequestMetadata;

final class PendingAuditLog
{
    private null|AuditLogActorData $actor = null;

    private null|string $bucket = null;

    private null|string $correlationId = null;

    private string $description = '';

    private null|string $id = null;

    /**
     * @var array<string, null|bool|float|int|string>
     */
    private array $metadata = [];

    private null|CarbonInterface $occurredAt = null;

    private null|string $remoteIp = null;

    private null|string $source = null;

    /**
     * @var list<AuditLogTargetData>
     */
    private array $targets = [];

    private string $tenantId = '';

    private null|string $userAgent = null;

    public function __construct(private readonly BackedEnum|string $event)
    {
    }

    public static function make(BackedEnum|string $event): self
    {
        return new self($event);
    }

    /**
     * @param array<string, null|bool|float|int|string> $metadata
     */
    public function actor(BackedEnum|string $type, int|string $id, null|string $name = null, array $metadata = []): self
    {
        $this->actor = new AuditLogActorData(
            id: $id,
            metadata: $metadata,
            name: $name,
            type: Enum::value($type),
        );

        return $this;
    }

    public function bucket(BackedEnum|string $bucket): self
    {
        $this->bucket = Enum::value($bucket);

        return $this;
    }

    public function correlationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param array<string, null|bool|float|int|string> $metadata
     */
    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function occurredAt(CarbonInterface $occurredAt): self
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }

    public function record(): void
    {
        resolve(AuditLogManager::class)->record($this->toAuditLogData());
    }

    public function remoteIp(string $remoteIp): self
    {
        $this->remoteIp = $remoteIp;

        return $this;
    }

    public function source(BackedEnum|string $source): self
    {
        $this->source = Enum::value($source);

        return $this;
    }

    /**
     * @param array<string, null|bool|float|int|string> $metadata
     */
    public function target(
        BackedEnum|string $type,
        int|string $id,
        null|string $name = null,
        array $metadata = [],
    ): self {
        $this->targets[] = new AuditLogTargetData(
            id: $id,
            metadata: $metadata,
            name: $name,
            type: Enum::value($type),
        );

        return $this;
    }

    public function tenant(int|string $id): self
    {
        $this->tenantId = (string) $id;

        return $this;
    }

    public function toAuditLogData(): AuditLogData
    {
        $requestMetadata = resolve(RequestMetadata::class);

        return new AuditLogData(
            actor: $this->actor ?? new AuditLogActorData(
                id: 'system',
                metadata: [],
                name: 'System',
                type: 'system',
            ),
            bucket: $this->bucket ?? Config::defaultsBucket(),
            event: Enum::value($this->event),
            source: $this->source ?? Config::defaultsSource(),
            correlationId: $this->correlationId,
            description: $this->description,
            id: $this->id,
            occurredAt: $this->occurredAt,
            metadata: $this->metadata,
            remoteIp: $this->remoteIp ?? $requestMetadata->remoteIp(),
            targets: $this->targets,
            tenantId: $this->tenantId,
            userAgent: $this->userAgent ?? $requestMetadata->userAgent(),
        );
    }

    public function userAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }
}
