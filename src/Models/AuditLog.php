<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Models;

use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\Support\Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Override;

/**
 * @property-read string $id
 * @property-read string $actor_id
 * @property-read array<string, mixed> $actor_metadata
 * @property-read null|string $actor_name
 * @property-read string $actor_type
 * @property-read string $bucket
 * @property-read null|string $correlation_id
 * @property-read string $description
 * @property-read string $event
 * @property-read null|CarbonImmutable $expires_at
 * @property-read CarbonImmutable $inserted_at
 * @property-read array<string, mixed> $metadata
 * @property-read CarbonImmutable $occurred_at
 * @property-read null|string $remote_ip
 * @property-read string $source
 * @property-read array<int, array<string, mixed>> $targets
 * @property-read string $tenant_id
 * @property-read null|string $user_agent
 */
class AuditLog extends Model
{
    use MassPrunable;

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'actor_metadata' => 'array',
        'expires_at' => 'immutable_datetime',
        'inserted_at' => 'immutable_datetime',
        'metadata' => 'array',
        'occurred_at' => 'immutable_datetime',
        'targets' => 'array',
    ];

    /**
     * @var array<string>
     */
    protected $guarded = [];

    protected $keyType = 'string';

    #[Override]
    public function getConnectionName(): null|string
    {
        return Config::storageConnection();
    }

    #[Override]
    public function getTable(): string
    {
        return Config::storageTable();
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query() // @phpstan-ignore-line staticMethod.dynamicCall
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Date::now());
    }
}
