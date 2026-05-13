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

class PendingAuditLog
{
    private null|AuditLogActorData $actor = null;

    private null|string $batchId = null;

    private null|string $bucket = null;

    private string $description = '';

    private null|string $id = null;

    private null|CarbonInterface $occurredAt = null;

    /**
     * @var array<string, null|bool|float|int|string>
     */
    private array $properties = [];

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
     * @param array<string, null|bool|float|int|string> $properties
     */
    public function actor(BackedEnum|string $type, string $id, null|string $name = null, array $properties = []): self
    {
        $this->actor = new AuditLogActorData(
            type: Enum::value($type),
            id: $id,
            name: $name,
            properties: $properties,
        );

        return $this;
    }

    public function batchId(string $batchId): self
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function bucket(BackedEnum|string $bucket): self
    {
        $this->bucket = Enum::value($bucket);

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

    public function occurredAt(CarbonInterface $occurredAt): self
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }

    /**
     * @param array<string, null|bool|float|int|string> $properties
     */
    public function properties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function record(): AuditLogData
    {
        $log = $this->toAuditLogData();

        resolve(AuditLogManager::class)->record($log);

        return $log;
    }

    public function remoteIp(null|string $remoteIp): self
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
     * @param array<string, null|bool|float|int|string> $properties
     */
    public function target(
        BackedEnum|string $type,
        string $id,
        null|string $name = null,
        array $properties = [],
    ): self {
        $this->targets[] = new AuditLogTargetData(
            type: Enum::value($type),
            id: $id,
            name: $name,
            properties: $properties,
        );

        return $this;
    }

    public function tenant(string $id): self
    {
        $this->tenantId = $id;

        return $this;
    }

    public function toAuditLogData(): AuditLogData
    {
        return new AuditLogData(
            actor: $this->actor ?? new AuditLogActorData(
                type: 'system',
                id: 'system',
                name: 'System',
            ),
            properties: $this->properties,
            targets: $this->targets,
            occurredAt: $this->occurredAt,
            batchId: $this->batchId,
            id: $this->id,
            remoteIp: $this->remoteIp,
            userAgent: $this->userAgent,
            bucket: $this->bucket ?? Config::defaultBucket(),
            description: $this->description,
            event: Enum::value($this->event),
            source: $this->source ?? Config::defaultSource(),
            tenantId: $this->tenantId,
        );
    }

    public function userAgent(null|string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }
}
