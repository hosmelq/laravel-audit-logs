<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Data;

use BackedEnum;
use HosmelQ\AuditLog\Support\Enum;

final readonly class AuditLogActorData
{
    public string $id;

    public string $type;

    public function __construct(
        int|string $id = '',
        /**
         * @var array<string, null|bool|float|int|string>
         */
        public array $metadata = [],
        public null|string $name = null,
        BackedEnum|string $type = '',
    ) {
        $this->id = (string) $id;
        $this->type = Enum::value($type);
    }

    /**
     * @return array{id: string, metadata: array<string, null|bool|float|int|string>, name: null|string, type: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'metadata' => $this->metadata,
            'name' => $this->name,
            'type' => $this->type,
        ];
    }
}
