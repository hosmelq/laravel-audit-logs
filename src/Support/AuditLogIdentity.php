<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Support;

use BackedEnum;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;

final readonly class AuditLogIdentity
{
    public string $id;

    public string $type;

    /**
     * @param array<string, null|bool|float|int|string> $metadata
     */
    public function __construct(
        int|string $id,
        BackedEnum|string $type,
        public array $metadata = [],
        public null|string $name = null,
    ) {
        $this->id = (string) $id;
        $this->type = Enum::value($type);
    }

    public function toActorData(): AuditLogActorData
    {
        return new AuditLogActorData(
            id: $this->id,
            metadata: $this->metadata,
            name: $this->name,
            type: $this->type,
        );
    }

    public function toTargetData(): AuditLogTargetData
    {
        return new AuditLogTargetData(
            id: $this->id,
            metadata: $this->metadata,
            name: $this->name,
            type: $this->type,
        );
    }
}
