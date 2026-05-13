<?php

declare(strict_types=1);

use function HosmelQ\AuditLog\audit_log;

use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;
use HosmelQ\AuditLog\Jobs\CommitAuditLogs as CommitAuditLogsJob;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('can commit pending audit logs through the helper manager', function (): void {
    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J00000000000000000000009',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    expect(audit_log()->commit())->toBe(1)
        ->and(DB::table('audit_logs')->where('id', '01J00000000000000000000009')->exists())->toBeTrue();
});

it('can queue captured audit log batches for retryable commits', function (): void {
    Config::set('audit-log.queue', [
        'connection' => 'redis',
        'enabled' => true,
        'name' => 'audit-logs',
    ]);

    Queue::fake();

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(
            type: 'user',
            id: 'member_123',
            name: 'Hosmel Quintana',
        ),
        id: '01J00000000000000000000006',
        description: 'Published document',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    expect(AuditLog::commit())->toBe(1);

    Queue::assertPushed(CommitAuditLogsJob::class, function (CommitAuditLogsJob $job): bool {
        return $job->connection === 'redis'
            && $job->queue === 'audit-logs'
            && count($job->logs) === 1
            && $job->logs[0]->id === '01J00000000000000000000006';
    });

    expect(DB::table('audit_logs')->where('id', '01J00000000000000000000006')->doesntExist())->toBeTrue();
});

it('stores audit logs with generated ulid ids', function (): void {
    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    expect(AuditLog::commit())->toBe(1);

    $row = DB::table('audit_logs')->first();

    expect(Str::isUlid($row->id))->toBeTrue();
});

it('stores real record many calls with a shared batch id', function (): void {
    AuditLog::recordMany([
        new AuditLogData(
            actor: new AuditLogActorData(type: 'user', id: 'member_123'),
            event: 'document.publish',
            tenantId: 'org_123',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(type: 'user', id: 'member_123'),
            event: 'document.archive',
            tenantId: 'org_123',
        ),
    ]);

    expect(AuditLog::commit())->toBe(2);

    $rows = DB::table('audit_logs')->orderBy('event')->get();

    expect($rows)->toHaveCount(2)
        ->and($rows[1]->batch_id)->toBe($rows[0]->batch_id);
});
