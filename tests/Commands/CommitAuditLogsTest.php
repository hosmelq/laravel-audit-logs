<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;
use HosmelQ\AuditLog\Jobs\CommitAuditLogs as CommitAuditLogsJob;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('reports when there are no pending audit logs', function (): void {
    artisan('audit-log:commit')
        ->expectsOutput('No audit logs to commit.')
        ->assertExitCode(0);
});

it('commits pending audit logs', function (): void {
    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J00000000000000000000002',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    artisan('audit-log:commit')
        ->expectsOutput('Committed 1 audit log(s).')
        ->assertExitCode(0);

    expect(DB::table('audit_logs')->where('id', '01J00000000000000000000002')->exists())->toBeTrue();
});

it('reports queued pending audit logs', function (): void {
    Config::set('audit-log.queue', [
        'connection' => 'redis',
        'enabled' => true,
        'name' => 'audit-logs',
    ]);

    Queue::fake();

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J0000000000000000000000B',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    artisan('audit-log:commit')
        ->expectsOutput('Queued 1 audit log(s) for commit.')
        ->assertExitCode(0);

    Queue::assertPushed(CommitAuditLogsJob::class);

    expect(DB::table('audit_logs')->where('id', '01J0000000000000000000000B')->doesntExist())->toBeTrue();
});
