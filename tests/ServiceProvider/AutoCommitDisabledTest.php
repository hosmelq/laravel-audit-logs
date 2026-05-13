<?php

declare(strict_types=1);

use function Orchestra\Testbench\Pest\defineEnvironment;

use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\DB;

defineEnvironment(function ($app): void {
    $app['config']->set('audit-log.auto_commit', false);
});

it('does not commit pending audit logs when the application terminates', function (): void {
    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J0000000000000000000000G',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    resolve(Application::class)->terminate();

    expect(DB::table('audit_logs')->where('id', '01J0000000000000000000000G')->doesntExist())->toBeTrue();
});

it('does not commit pending audit logs after a queue job is processed', function (): void {
    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        id: '01J0000000000000000000000H',
        event: 'document.publish',
        tenantId: 'org_123',
    ));

    event(new JobProcessed('sync', null));

    expect(DB::table('audit_logs')->where('id', '01J0000000000000000000000H')->doesntExist())->toBeTrue();
});
