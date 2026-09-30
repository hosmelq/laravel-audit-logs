<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Models;

use BackedEnum;
use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\Contracts\HasAuditLogIdentity;
use HosmelQ\AuditLog\Exceptions\InvalidAuditLogIdentity;
use HosmelQ\AuditLog\Support\AuditLogTargetsExpression;
use HosmelQ\AuditLog\Support\Config;
use HosmelQ\AuditLog\Support\Enum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Illuminate\Support\Facades\Date;
use Override;

/**
 * @property-read string $id
 * @property-read string $actor_id
 * @property-read array<string, mixed> $actor_metadata
 * @property-read null|string $actor_name
 * @property-read string $actor_type
 * @property-read string $bucket
 * @property-read null|array{before: array<string, mixed>, after: array<string, mixed>} $changes
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
        'changes' => 'array',
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

    /**
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopeForActor(Builder $query, BackedEnum|HasAuditLogIdentity|string $type, null|int|string $id = null): Builder
    {
        if ($type instanceof HasAuditLogIdentity) {
            $identity = $type->auditLogIdentity();
            $id = $identity->id;
            $type = $identity->type;
        }

        if ($id === null) {
            throw InvalidAuditLogIdentity::missingActorId();
        }

        return $query
            ->where($this->qualifyColumn('actor_type'), Enum::value($type))
            ->where($this->qualifyColumn('actor_id'), (string) $id);
    }

    /**
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopeForCorrelation(Builder $query, null|string $id): Builder
    {
        return $query->where($this->qualifyColumn('correlation_id'), $id);
    }

    /**
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopeForEvent(Builder $query, BackedEnum|string $event): Builder
    {
        return $query->where($this->qualifyColumn('event'), Enum::value($event));
    }

    /**
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopeForTarget(Builder $query, BackedEnum|HasAuditLogIdentity|string $type, null|int|string $id = null): Builder
    {
        if ($type instanceof HasAuditLogIdentity) {
            $identity = $type->auditLogIdentity();
            $id = $identity->id;
            $type = $identity->type;
        }

        if ($id === null) {
            throw InvalidAuditLogIdentity::missingTargetId();
        }

        $type = Enum::value($type);
        $id = (string) $id;
        $column = $this->qualifyColumn('targets');
        $baseQuery = $query->getQuery();
        $grammar = $baseQuery->getGrammar();

        if ($grammar instanceof SQLiteGrammar || $grammar instanceof SqlServerGrammar) {
            $function = $grammar instanceof SQLiteGrammar ? 'json_each' : 'openjson';

            $baseQuery->whereExists(function (QueryBuilder $targets) use ($column, $function, $id, $type): void {
                $targets->selectRaw('1')
                    ->from(new AuditLogTargetsExpression($column, $function))
                    ->where('audit_log_targets.value->type', $type)
                    ->where('audit_log_targets.value->id', $id);
            });

            return $query;
        }

        $baseQuery->whereJsonContains($column, [['id' => $id, 'type' => $type]]);

        return $query;
    }

    /**
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopeForTenant(Builder $query, int|string $id): Builder
    {
        return $query->where($this->qualifyColumn('tenant_id'), (string) $id);
    }

    /**
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopeFromSource(Builder $query, BackedEnum|string $source): Builder
    {
        return $query->where($this->qualifyColumn('source'), Enum::value($source));
    }

    /**
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function scopeInBucket(Builder $query, BackedEnum|string $bucket): Builder
    {
        return $query->where($this->qualifyColumn('bucket'), Enum::value($bucket));
    }
}
