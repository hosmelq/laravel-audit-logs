<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Support;

use HosmelQ\AuditLog\Data\AuditLogData;
use JsonException;

class JsonAuditLogEncoder
{
    /**
     * @throws JsonException
     */
    public function decode(string $payload): AuditLogData
    {
        /** @var array{
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
         * } $attributes */
        $attributes = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        return AuditLogData::fromArray($attributes);
    }

    /**
     * @throws JsonException
     */
    public function encode(AuditLogData $log): string
    {
        return json_encode($log->toArray(), JSON_THROW_ON_ERROR);
    }
}
