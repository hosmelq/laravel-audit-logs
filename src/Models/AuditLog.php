<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Models;

use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\Support\Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Override;

/**
 * @property null|CarbonImmutable $expires_at
 */
class AuditLog extends Model
{
    use HasUlids;
    use MassPrunable;

    public $timestamps = false;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'actor_properties' => 'array',
        'expires_at' => 'immutable_datetime',
        'inserted_at' => 'immutable_datetime',
        'occurred_at' => 'immutable_datetime',
        'properties' => 'array',
        'targets' => 'array',
    ];

    /**
     * @var array<string>
     */
    protected $guarded = [];

    #[Override]
    public function getTable(): string
    {
        return Config::logsTable();
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
