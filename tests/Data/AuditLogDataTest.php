<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\AuditLogId;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

it('generates an id when none is provided', function (): void {
    Str::createUlidsUsingSequence([new Ulid('01HX0000000000000000000000')]);

    $log = new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
    );

    expect($log->id)->toBe('log_01HX0000000000000000000000');
});

it('uses the registered audit log id generator', function (): void {
    app()->scoped(AuditLogId::class, fn (): AuditLogId => new class () extends AuditLogId {
        public function log(): string
        {
            return 'custom-log-id';
        }
    });

    $log = new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
    );

    expect($log->id)->toBe('custom-log-id');
});

it('normalizes numeric entity identifiers', function (): void {
    $log = new AuditLogData(
        actor: new AuditLogActorData(id: 123),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
        targets: [new AuditLogTargetData(id: 456)],
        tenantId: 789,
    );

    expect($log)
        ->actor->id->toBe('123')
        ->targets()->{0}->id->toBe('456')
        ->tenantId->toBe('789');
});

it('normalizes blank correlation ids', function (): void {
    $log = new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
        correlationId: '   ',
    );

    expect($log)->correlationId->toBeNull();
});

it('normalizes targets to a list', function (): void {
    $log = new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
        targets: ['account' => new AuditLogTargetData(id: 'account-1')],
    );

    expect($log->targets())->toBe([
        [
            'id' => 'account-1',
            'metadata' => [],
            'name' => null,
            'type' => '',
        ],
    ]);
});

it('serializes audit log data to arrays', function (): void {
    $log = new AuditLogData(
        actor: new AuditLogActorData(
            id: 'user-1',
            metadata: ['role' => 'admin'],
            name: 'Jane Doe',
            type: 'user',
        ),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
        correlationId: 'correlation-1',
        description: 'Account updated.',
        id: 'log-1',
        occurredAt: CarbonImmutable::parse('2026-05-25 10:00:00.123 UTC'),
        metadata: ['status' => 'active'],
        remoteIp: '127.0.0.1',
        targets: [
            new AuditLogTargetData(
                id: 'account-1',
                metadata: ['plan' => 'pro'],
                name: 'Acme',
                type: 'account',
            ),
        ],
        tenantId: 'tenant-1',
        userAgent: 'Browser',
    );

    expect($log->toArray())->toBe([
        'actor' => [
            'id' => 'user-1',
            'metadata' => ['role' => 'admin'],
            'name' => 'Jane Doe',
            'type' => 'user',
        ],
        'bucket' => 'security',
        'correlation_id' => 'correlation-1',
        'description' => 'Account updated.',
        'event' => 'account.updated',
        'id' => 'log-1',
        'metadata' => ['status' => 'active'],
        'occurred_at' => '2026-05-25T10:00:00.123+00:00',
        'remote_ip' => '127.0.0.1',
        'source' => 'tests',
        'targets' => [
            [
                'id' => 'account-1',
                'metadata' => ['plan' => 'pro'],
                'name' => 'Acme',
                'type' => 'account',
            ],
        ],
        'tenant_id' => 'tenant-1',
        'user_agent' => 'Browser',
    ]);
});

it('returns a copy with a correlation id', function (): void {
    $log = new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
    );

    $correlated = $log->withCorrelationId('correlation-1');

    expect($log)
        ->correlationId->toBeNull()
        ->id->toBe($correlated->id)
        ->and($correlated)->correlationId->toBe('correlation-1');
});
