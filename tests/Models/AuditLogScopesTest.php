<?php

declare(strict_types=1);

use HosmelQ\AuditLog\AuditLogContext;
use HosmelQ\AuditLog\AuditLogWriter;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use HosmelQ\AuditLog\Exceptions\InvalidAuditLogIdentity;
use HosmelQ\AuditLog\Models\AuditLog;
use HosmelQ\AuditLog\Tests\TestSupport\TestEvent;
use HosmelQ\AuditLog\Tests\TestSupport\TestIdentityType;
use HosmelQ\AuditLog\Tests\TestSupport\TestOrganization;
use HosmelQ\AuditLog\Tests\TestSupport\TestUser;
use Illuminate\Database\Eloquent\Builder;

beforeEach(function (): void {
    $logs = [];

    foreach ([
        ['id' => 'base'],
        ['id' => 'tenant', 'tenant_id' => 'other'],
        ['id' => 'actor-id', 'actor_id' => 'other'],
        ['id' => 'actor-type', 'actor_type' => 'service'],
        ['id' => 'bucket', 'bucket' => 'other'],
        ['id' => 'event', 'event' => 'account.closed'],
        ['id' => 'source', 'source' => 'worker'],
        ['id' => 'correlation', 'correlation_id' => 'other'],
        ['id' => 'uncorrelated', 'correlation_id' => null],
        ['id' => 'no-targets', 'targets' => []],
        ['id' => 'target', 'targets' => [
            new AuditLogTargetData(id: 'other', type: 'organization'),
            new AuditLogTargetData(id: 'organization-1', type: 'account'),
        ]],
    ] as $attributes) {
        $logs[] = new AuditLogData(
            actor: new AuditLogActorData(id: $attributes['actor_id'] ?? 'user-1', type: $attributes['actor_type'] ?? 'user'),
            bucket: $attributes['bucket'] ?? 'security',
            event: $attributes['event'] ?? 'account.updated',
            source: $attributes['source'] ?? 'platform',
            correlationId: array_key_exists('correlation_id', $attributes) ? $attributes['correlation_id'] : 'correlation-1',
            id: $attributes['id'],
            targets: $attributes['targets'] ?? [
                new AuditLogTargetData(id: 'account-1', type: 'account'),
                new AuditLogTargetData(id: 'organization-1', metadata: ['plan' => 'pro'], name: 'Acme', type: 'organization'),
            ],
            tenantId: $attributes['tenant_id'] ?? 123,
        );
    }

    resolve(AuditLogWriter::class)->write($logs);
});

it('rejects scalar actor queries without an id', function (): void {
    expect(fn () => AuditLog::query()->forActor('user'))
        ->toThrow(InvalidAuditLogIdentity::class, 'An actor id is required');
});

it('rejects scalar target queries without an id', function (): void {
    expect(fn () => AuditLog::query()->forTarget('organization'))
        ->toThrow(InvalidAuditLogIdentity::class, 'A target id is required');
});

it('does not apply implicit tenant filters from configured context', function (): void {
    AuditLogContext::resolveTenantUsing(fn (): string => 'other');

    expect(AuditLog::query()->forTarget(new TestOrganization())->forTenant(123)->count())->toBe(8);
});

it('filters logs with reusable scopes', function (Closure $query, array $excluded): void {
    $expected = array_values(array_diff([
        'actor-id', 'actor-type', 'base', 'bucket', 'correlation', 'event', 'no-targets', 'source', 'target', 'tenant', 'uncorrelated',
    ], $excluded));

    expect($query()->orderBy('id')->pluck('id')->all())->toBe($expected);
})->with([
    'tenant' => [fn (): Builder => AuditLog::query()->forTenant(123), ['tenant']],
    'actor' => [fn (): Builder => AuditLog::query()->forActor('user', 'user-1'), ['actor-id', 'actor-type']],
    'actor enum' => [fn (): Builder => AuditLog::query()->forActor(TestIdentityType::User, 'user-1'), ['actor-id', 'actor-type']],
    'actor identity' => [fn (): Builder => AuditLog::query()->forActor(new TestUser()), ['actor-id', 'actor-type']],
    'target' => [fn (): Builder => AuditLog::query()->forTarget('organization', 'organization-1'), ['no-targets', 'target']],
    'target enum' => [fn (): Builder => AuditLog::query()->forTarget(TestIdentityType::Organization, 'organization-1'), ['no-targets', 'target']],
    'target identity' => [fn (): Builder => AuditLog::query()->forTarget(new TestOrganization()), ['no-targets', 'target']],
    'event' => [fn (): Builder => AuditLog::query()->forEvent(TestEvent::AccountUpdated), ['event']],
    'bucket' => [fn (): Builder => AuditLog::query()->inBucket('security'), ['bucket']],
    'source' => [fn (): Builder => AuditLog::query()->fromSource('platform'), ['source']],
    'correlation' => [fn (): Builder => AuditLog::query()->forCorrelation('correlation-1'), ['correlation', 'uncorrelated']],
]);

it('finds uncorrelated logs with a null correlation id', function (): void {
    expect(AuditLog::query()->forCorrelation(null)->pluck('id')->all())->toBe(['uncorrelated']);
});

it('normalizes zero actor and target identifiers', function (): void {
    resolve(AuditLogWriter::class)->write([
        new AuditLogData(
            actor: new AuditLogActorData(id: 0, type: 'user'),
            bucket: 'security',
            event: 'account.updated',
            source: 'platform',
            id: 'zero',
            targets: [new AuditLogTargetData(id: 0, type: 'account')],
        ),
    ]);

    expect(AuditLog::query()->forActor('user', 0)->forTarget('account', 0)->pluck('id')->all())->toBe(['zero']);
});

it('combines all scopes in joined queries without ambiguous columns', function (): void {
    $logs = AuditLog::query()
        ->join('audit_logs as related_logs', 'audit_logs.id', '=', 'related_logs.id')
        ->forTenant(123)
        ->forActor(type: 'user', id: 'user-1')
        ->forTarget(type: 'organization', id: 'organization-1')
        ->forEvent(TestEvent::AccountUpdated)
        ->inBucket('security')
        ->fromSource('platform')
        ->forCorrelation('correlation-1')
        ->pluck('audit_logs.id')
        ->all();

    expect($logs)->toBe(['base']);
});

it('preserves grouped actor filters in alternative queries', function (): void {
    expect(AuditLog::query()->where('id', 'base')
        ->orWhere(fn (Builder $query): Builder => $query->forActor('service', 'user-1'))
        ->orderBy('id')->pluck('id')->all())->toBe(['actor-type', 'base']);
});
