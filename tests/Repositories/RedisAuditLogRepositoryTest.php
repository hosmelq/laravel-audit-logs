<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;
use HosmelQ\AuditLog\Jobs\CommitAuditLogs as CommitAuditLogsJob;
use HosmelQ\AuditLog\Repositories\RedisAuditLogRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

beforeEach(fn (): mixed => Redis::flushdb());

afterEach(fn (): mixed => Redis::flushdb());

it('returns no audit logs when redis has no pending list', function (): void {
    $repository = resolve(RedisAuditLogRepository::class);

    expect($repository->all())->toBe([]);
});

it('stores drains and flushes audit logs from redis', function (): void {
    $repository = resolve(RedisAuditLogRepository::class);

    $repository->add(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J00000000000000000000003',
        event: 'document.publish',
        tenantId: 'org_123',
    ));
    $repository->add(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J0000000000000000000000C',
        event: 'document.archive',
        tenantId: 'org_123',
    ));

    $logs = $repository->all();

    expect($logs)->toHaveCount(2)
        ->and($logs[0])
        ->event->toBe('document.publish')
        ->id->toBe('01J00000000000000000000003')
        ->tenantId->toBe('org_123')
        ->and($logs[1])
        ->event->toBe('document.archive')
        ->id->toBe('01J0000000000000000000000C');

    $repository->flush();

    expect($repository->all())->toBe([]);
});

it('commits audit logs through the redis driver flow', function (): void {
    Config::set('audit-log.driver', 'redis');

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J0000000000000000000000D',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    expect(AuditLog::commit())->toBe(1)
        ->and(DB::table('audit_logs')->where('id', '01J0000000000000000000000D')->exists())->toBeTrue()
        ->and(AuditLog::commit())->toBe(0);
});

it('queues audit logs committed from redis without duplicating them', function (): void {
    Config::set('audit-log.driver', 'redis');
    Config::set('audit-log.queue', [
        'connection' => 'redis',
        'enabled' => true,
        'name' => 'audit-logs',
    ]);

    Queue::fake();

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J0000000000000000000000I',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    expect(AuditLog::commit())->toBe(1);

    Queue::assertPushed(CommitAuditLogsJob::class, function (CommitAuditLogsJob $job): bool {
        return $job->connection === 'redis'
            && $job->queue === 'audit-logs'
            && count($job->logs) === 1
            && $job->logs[0]->id === '01J0000000000000000000000I';
    });

    expect(AuditLog::commit())->toBe(0);
});
