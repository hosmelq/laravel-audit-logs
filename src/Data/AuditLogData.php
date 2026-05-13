<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Data;

use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use HosmelQ\AuditLog\Support\Enum;
use Illuminate\Queue\SerializesModels;

class AuditLogData
{
    use SerializesModels;

    public string $bucket;

    public string $event;

    public string $source;

    /**
     * @param array<string, null|bool|float|int|string> $properties
     * @param list<AuditLogTargetData> $targets
     */
    public function __construct(
        public AuditLogActorData $actor,
        public array $properties = [],
        public array $targets = [],
        public null|CarbonInterface $occurredAt = null,
        public null|string $batchId = null,
        public null|string $id = null,
        public null|string $remoteIp = null,
        public null|string $userAgent = null,
        BackedEnum|string $bucket = 'audit_logs',
        public string $description = '',
        BackedEnum|string $event = '',
        BackedEnum|string $source = 'platform',
        public string $tenantId = '',
    ) {
        $this->bucket = Enum::value($bucket);
        $this->event = Enum::value($event);
        $this->source = Enum::value($source);
        $this->occurredAt ??= new CarbonImmutable();
    }

    /**
     * @param  array{
     *     tenant_id: string,
     *     event: string,
     *     description: string,
     *     actor: array{type: string, id: string, name?: null|string, properties?: array<string, null|bool|float|int|string>},
     *     targets?: list<array{type: string, id: string, name?: null|string, properties?: array<string, null|bool|float|int|string>}>,
     *     bucket?: string,
     *     source?: string,
     *     id?: null|string,
     *     batch_id?: null|string,
     *     occurred_at?: int|string,
     *     remote_ip?: null|string,
     *     user_agent?: null|string,
     *     properties?: array<string, null|bool|float|int|string>
     * }  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            actor: AuditLogActorData::fromArray($attributes['actor']),
            properties: $attributes['properties'] ?? [],
            targets: array_map(
                AuditLogTargetData::fromArray(...),
                $attributes['targets'] ?? [],
            ),
            occurredAt: self::dateFrom($attributes['occurred_at'] ?? null),
            batchId: $attributes['batch_id'] ?? null,
            id: $attributes['id'] ?? null,
            remoteIp: $attributes['remote_ip'] ?? null,
            userAgent: $attributes['user_agent'] ?? null,
            bucket: $attributes['bucket'] ?? 'audit_logs',
            description: $attributes['description'],
            event: $attributes['event'],
            source: $attributes['source'] ?? 'platform',
            tenantId: $attributes['tenant_id'],
        );
    }

    /**
     * @return list<array{type: string, id: string, name: null|string, properties: array<string, null|bool|float|int|string>}>
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
     *     tenant_id: string,
     *     event: string,
     *     description: string,
     *     actor: array{type: string, id: string, name: null|string, properties: array<string, null|bool|float|int|string>},
     *     targets: list<array{type: string, id: string, name: null|string, properties: array<string, null|bool|float|int|string>}>,
     *     bucket: string,
     *     source: string,
     *     id: null|string,
     *     batch_id: null|string,
     *     occurred_at: string,
     *     remote_ip: null|string,
     *     user_agent: null|string,
     *     properties: array<string, null|bool|float|int|string>
     * }
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'event' => $this->event,
            'description' => $this->description,
            'actor' => $this->actor->toArray(),
            'targets' => array_map(
                static fn (AuditLogTargetData $target): array => $target->toArray(),
                $this->targets,
            ),
            'bucket' => $this->bucket,
            'source' => $this->source,
            'id' => $this->id,
            'batch_id' => $this->batchId,
            'occurred_at' => $this->dateToString($this->occurredAt ?? new CarbonImmutable()),
            'remote_ip' => $this->remoteIp,
            'user_agent' => $this->userAgent,
            'properties' => $this->properties,
        ];
    }

    private static function dateFrom(null|int|string $value): null|CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || ctype_digit($value)) {
            return CarbonImmutable::createFromTimestampMsUTC($value);
        }

        return CarbonImmutable::parse($value);
    }

    private function dateToString(CarbonInterface $date): string
    {
        return CarbonImmutable::createFromInterface($date)->format('Y-m-d\TH:i:s.vP');
    }
}
