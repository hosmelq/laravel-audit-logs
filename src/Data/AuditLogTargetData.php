<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Data;

use BackedEnum;
use HosmelQ\AuditLog\Support\Enum;

readonly class AuditLogTargetData
{
    public string $type;

    public function __construct(
        BackedEnum|string $type = '',
        public string $id = '',
        public null|string $name = null,
        /**
         * @var array<string, null|bool|float|int|string>
         */
        public array $properties = [],
    ) {
        $this->type = Enum::value($type);
    }

    /**
     * @param array{type: string, id: string, name?: null|string, properties?: array<string, null|bool|float|int|string>} $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            type: $attributes['type'],
            id: $attributes['id'],
            name: $attributes['name'] ?? null,
            properties: $attributes['properties'] ?? [],
        );
    }

    /**
     * @return array{type: string, id: string, name: null|string, properties: array<string, null|bool|float|int|string>}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'name' => $this->name,
            'properties' => $this->properties,
        ];
    }
}
