<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\AuditLogWriter;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use HosmelQ\AuditLog\Models\AuditLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('rolls back all chunks when one insert fails', function (): void {
    Config::set('audit-log.storage.insert_chunk_size', 1);

    $writer = resolve(AuditLogWriter::class);

    expect(fn () => $writer->write([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-1',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-1',
        ),
    ]))
        ->toThrow(QueryException::class)
        ->and(AuditLog::query()->count())->toBe(0);
});

it('throws when payloads cannot be encoded as json', function (): void {
    expect(fn () => resolve(AuditLogWriter::class)->write([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            metadata: ['score' => NAN],
        ),
    ]))
        ->toThrow(JsonException::class);
});

it('writes audit logs to the configured table', function (): void {
    Config::set('database.migrations', 'audit_writer_migrations');
    Config::set('audit-log.storage.table', 'custom_audit_logs');

    $migrationPath = dirname(__DIR__).'/database/migrations';

    app()->forgetInstance('migration.repository');
    app()->forgetInstance('migrator');

    $migrationRepository = app()->make('migration.repository');

    if (! $migrationRepository->repositoryExists()) {
        $migrationRepository->createRepository();
    }

    app()->make('migrator')->run([$migrationPath]);

    resolve(AuditLogWriter::class)->write([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
        ),
    ]);

    expect(DB::table('custom_audit_logs')->count())
        ->toBe(1)
        ->and(DB::table('audit_logs')->count())->toBe(0);
});

it('writes audit log payloads to the database', function (): void {
    resolve(AuditLogWriter::class)->write([
        new AuditLogData(
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
            description: 'User changed account settings.',
            metadata: ['count' => 3, 'enabled' => true],
            remoteIp: '127.0.0.1',
            targets: [
                new AuditLogTargetData(id: 'account-1'),
            ],
            tenantId: 'tenant-1',
            userAgent: 'AuditLog Test',
        ),
    ]);

    $row = DB::table('audit_logs')->first();

    expect($row)
        ->actor_id->toBe('user-1')
        ->actor_name->toBe('Jane Doe')
        ->actor_type->toBe('user')
        ->bucket->toBe('security')
        ->correlation_id->toBe('correlation-1')
        ->description->toBe('User changed account settings.')
        ->remote_ip->toBe('127.0.0.1')
        ->source->toBe('tests')
        ->tenant_id->toBe('tenant-1')
        ->user_agent->toBe('AuditLog Test')
        ->metadata->json()->toBe(['count' => 3, 'enabled' => true])
        ->actor_metadata->json()->toBe(['role' => 'admin'])
        ->targets->json()->{0}->id->toBe('account-1');

    expect(DB::table('audit_logs')
        ->selectRaw("JSON_TYPE(JSON_EXTRACT(targets, '$[0].metadata')) as metadata_type")
        ->value('metadata_type'))->toBe('OBJECT');
});

it('keeps the default occurrence time from when the log was created', function (): void {
    $log = new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
    );

    $this->travel(5)->seconds();

    resolve(AuditLogWriter::class)->write([$log]);

    $row = AuditLog::query()->firstOrFail();

    expect($row)
        ->inserted_at->greaterThan($row->occurred_at)->toBeTrue()
        ->occurred_at->equalTo($log->occurredAt)->toBeTrue();
});

it('sets expires_at from the retention configuration', function (): void {
    Config::set('audit-log.retention.days', 30);

    $occurredAt = CarbonImmutable::parse('2026-05-01 08:00:00.000');

    resolve(AuditLogWriter::class)->write([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            occurredAt: $occurredAt,
        ),
    ]);

    $expiresAt = DB::table('audit_logs')->value('expires_at');

    expect(CarbonImmutable::parse($expiresAt)->equalTo($occurredAt->addDays(30)))->toBeTrue();
});
