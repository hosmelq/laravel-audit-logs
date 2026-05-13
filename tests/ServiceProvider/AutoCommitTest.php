<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('commits pending audit logs when the application terminates', function (): void {
    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J0000000000000000000000E',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    resolve(Application::class)->terminate();

    expect(DB::table('audit_logs')->where('id', '01J0000000000000000000000E')->exists())->toBeTrue();
});

it('commits pending audit logs after a queue job is processed', function (): void {
    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J0000000000000000000000F',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    event(new JobProcessed('sync', null));

    expect(DB::table('audit_logs')->where('id', '01J0000000000000000000000F')->exists())->toBeTrue();
});

it('discards pending audit logs after a queue job throws an exception', function (): void {
    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J0000000000000000000000G',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    event(new JobExceptionOccurred('sync', null, new RuntimeException('Job failed.')));
    event(new JobProcessed('sync', null));

    expect(DB::table('audit_logs')->where('id', '01J0000000000000000000000G')->doesntExist())->toBeTrue();
});

it('can commit pending audit logs after a queue job throws an exception', function (): void {
    Config::set('audit-log.queue.commit_after_failure', true);

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J0000000000000000000000H',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    event(new JobExceptionOccurred('sync', null, new RuntimeException('Job failed.')));

    expect(DB::table('audit_logs')->where('id', '01J0000000000000000000000H')->exists())->toBeTrue();
});
