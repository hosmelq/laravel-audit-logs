<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use HosmelQ\AuditLog\AuditLogId;
use Illuminate\Support\Str;

final readonly class AuditLogData
{
    public null|string $correlationId;

    public string $id;

    public CarbonImmutable $occurredAt;

    /**
     * @var list<AuditLogTargetData>
     */
    public array $targets;

    public string $tenantId;

    /**
     * @param array<string, null|bool|float|int|string> $metadata
     * @param array<array-key, AuditLogTargetData> $targets
     */
    public function __construct(
        public AuditLogActorData $actor,
        public string $bucket,
        public string $event,
        public string $source,
        null|string $correlationId = null,
        public string $description = '',
        null|string $id = null,
        null|CarbonInterface $occurredAt = null,
        public array $metadata = [],
        public null|string $remoteIp = null,
        array $targets = [],
        int|string $tenantId = '',
        public null|string $userAgent = null,
    ) {
        $this->correlationId = $correlationId !== null && Str::trim($correlationId) === ''
            ? null
            : $correlationId;
        $this->id = $id ?? resolve(AuditLogId::class)->log();
        $this->occurredAt = $occurredAt instanceof CarbonInterface
            ? CarbonImmutable::createFromInterface($occurredAt)
            : new CarbonImmutable();
        $this->targets = array_values($targets);
        $this->tenantId = (string) $tenantId;
    }

    /**
     * @return list<array{id: string, metadata: array<string, null|bool|float|int|string>, name: null|string, type: string}>
     */
    public function targets(): array
    {
        return array_map(
            static fn (AuditLogTargetData $target): array => $target->toArray(),
            $this->targets,
        );
    }

    /**
     * @return array{
     *     actor: array{id: string, metadata: array<string, null|bool|float|int|string>, name: null|string, type: string},
     *     bucket: string,
     *     correlation_id: null|string,
     *     description: string,
     *     event: string,
     *     id: string,
     *     metadata: array<string, null|bool|float|int|string>,
     *     occurred_at: string,
     *     remote_ip: null|string,
     *     source: string,
     *     targets: list<array{id: string, metadata: array<string, null|bool|float|int|string>, name: null|string, type: string}>,
     *     tenant_id: string,
     *     user_agent: null|string,
     * }
     */
    public function toArray(): array
    {
        return [
            'actor' => $this->actor->toArray(),
            'bucket' => $this->bucket,
            'correlation_id' => $this->correlationId,
            'description' => $this->description,
            'event' => $this->event,
            'id' => $this->id,
            'metadata' => $this->metadata,
            'occurred_at' => $this->occurredAt->format('Y-m-d\TH:i:s.vP'),
            'remote_ip' => $this->remoteIp,
            'source' => $this->source,
            'targets' => $this->targets(),
            'tenant_id' => $this->tenantId,
            'user_agent' => $this->userAgent,
        ];
    }

    public function withCorrelationId(string $correlationId): self
    {
        return new self(
            actor: $this->actor,
            bucket: $this->bucket,
            event: $this->event,
            source: $this->source,
            correlationId: $correlationId,
            description: $this->description,
            id: $this->id,
            occurredAt: $this->occurredAt,
            metadata: $this->metadata,
            remoteIp: $this->remoteIp,
            targets: $this->targets,
            tenantId: $this->tenantId,
            userAgent: $this->userAgent,
        );
    }
}
