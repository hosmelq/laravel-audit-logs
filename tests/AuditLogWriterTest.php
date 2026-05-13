<?php

declare(strict_types=1);

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\AuditLogWriter;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use HosmelQ\AuditLog\DatabaseAuditLogManager;
use HosmelQ\AuditLog\Facades\AuditLog;
use HosmelQ\AuditLog\Models\AuditLog as AuditLogModel;
use HosmelQ\AuditLog\Tests\TestSupport\CustomAuditLog;
use HosmelQ\AuditLog\Tests\TestSupport\InvalidAuditLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

afterEach(function (): void {
    DatabaseAuditLogManager::useModel(AuditLogModel::class);
});

it('does not write empty audit log batches', function (): void {
    resolve(AuditLogWriter::class)->write([]);

    expect(DB::table('audit_logs')->count())->toBe(0);
});

it('fails loudly when audit log ids are duplicated', function (): void {
    AuditLog::recordMany([
        new AuditLogData(
            actor: new AuditLogActorData(type: 'user', id: 'member_123'),
            id: '01J00000000000000000000007',
            event: 'document.publish',
            tenantId: 'org_123',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(type: 'user', id: 'member_123'),
            id: '01J00000000000000000000007',
            event: 'document.archive',
            tenantId: 'org_123',
        ),
    ]);

    expect(fn (): int => AuditLog::commit())->toThrow(QueryException::class)
        ->and(DB::table('audit_logs')->count())->toBe(0);
});

it('rejects custom audit log models that do not extend the package model', function (): void {
    expect(fn (): mixed => DatabaseAuditLogManager::useModel(InvalidAuditLog::class))
        ->toThrow(
            InvalidArgumentException::class,
            sprintf(
                'Audit log model [%s] must extend [%s].',
                InvalidAuditLog::class,
                AuditLogModel::class,
            ),
        );
});

it('stores audit logs using the event payload shape', function (): void {
    Config::set('audit-log.retention.days', 30);

    $occurredAt = CarbonImmutable::createFromTimestampMsUTC(1_700_000_000_123);

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(
            type: 'user',
            id: 'member_123',
            name: 'Hosmel Quintana',
            properties: ['team' => 'development'],
        ),
        properties: ['request_id' => 'req_123'],
        targets: [
            new AuditLogTargetData(
                type: 'document',
                id: 'doc_123',
                name: 'Quarterly report',
                properties: ['folder' => 'reports'],
            ),
        ],
        occurredAt: $occurredAt,
        batchId: 'batch_123',
        id: '01J00000000000000000000005',
        remoteIp: '127.0.0.1',
        userAgent: 'Pest',
        description: 'Published document',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    expect(AuditLog::commit())->toBe(1);

    $row = DB::table('audit_logs')->where('id', '01J00000000000000000000005')->first();

    expect(CarbonImmutable::parse($row->occurred_at)->getTimestampMs())->toBe(1_700_000_000_123)
        ->and(CarbonImmutable::parse($row->expires_at)->getTimestampMs())->toBe(1_702_592_000_123)
        ->and($row)
        ->actor_id->toBe('member_123')
        ->actor_name->toBe('Hosmel Quintana')
        ->actor_properties->json()->toBe(['team' => 'development'])
        ->actor_type->toBe('user')
        ->batch_id->toBe('batch_123')
        ->bucket->toBe('audit_logs')
        ->description->toBe('Published document')
        ->event->toBe('document.publish')
        ->properties->json()->toBe(['request_id' => 'req_123'])
        ->remote_ip->toBe('127.0.0.1')
        ->source->toBe('platform')
        ->targets->json()->toBe([
            [
                'type' => 'document',
                'id' => 'doc_123',
                'name' => 'Quarterly report',
                'properties' => ['folder' => 'reports'],
            ],
        ])
        ->tenant_id->toBe('org_123')
        ->user_agent->toBe('Pest');
});

it('does not mutate mutable occurrence dates while writing', function (): void {
    $occurredAt = Carbon::createFromTimestampMsUTC(1_700_000_000_123)
        ->setTimezone('America/Managua');

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        occurredAt: $occurredAt,
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    expect(AuditLog::commit())->toBe(1);

    expect($occurredAt->timezoneName)->toBe('America/Managua');
});

it('stores occurrence dates with millisecond precision', function (): void {
    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        occurredAt: CarbonImmutable::createFromTimestampMsUTC(1_700_000_000_123),
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    expect(AuditLog::commit())->toBe(1);

    $row = DB::table('audit_logs')->first();

    expect($row->occurred_at)->toBe('2023-11-14 22:13:20.123');
});

it('uses the configured audit log model for synchronous commits', function (): void {
    DatabaseAuditLogManager::useModel(CustomAuditLog::class);

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    expect(AuditLog::commit())->toBe(1)
        ->and(DB::table('audit_logs')->where('id', '01J0000000000000000000000A')->exists())->toBeTrue();
});

it('uses the configured audit log model for queued commits', function (): void {
    Config::set('audit-log.queue', [
        'connection' => 'sync',
        'enabled' => true,
        'name' => null,
    ]);

    DatabaseAuditLogManager::useModel(CustomAuditLog::class);

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    expect(AuditLog::commit())->toBe(1)
        ->and(DB::table('audit_logs')->where('id', '01J0000000000000000000000A')->exists())->toBeTrue();
});
