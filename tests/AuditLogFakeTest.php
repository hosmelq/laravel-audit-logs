<?php

declare(strict_types=1);

use function HosmelQ\AuditLog\audit_log;

use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;
use Illuminate\Support\Str;

it('can assert no audit logs were recorded', function (): void {
    AuditLog::fake();

    AuditLog::assertNothingRecorded();
});

it('can fake recorded audit logs', function (): void {
    AuditLog::fake();

    audit_log('document.publish')
        ->tenant('org_123')
        ->actor(type: 'user', id: 'member_123')
        ->record();

    AuditLog::assertRecorded('document.publish');
    AuditLog::assertRecorded(fn (AuditLogData $log): bool => $log->tenantId === 'org_123');
    AuditLog::assertRecorded(fn (AuditLogData $log): bool => is_string($log->id) && Str::isUlid($log->id));
    AuditLog::assertRecordedTimes('document.publish');
    AuditLog::assertNotRecorded('document.archive');

    expect(AuditLog::recorded('document.publish'))->toHaveCount(1)
        ->and(AuditLog::commit())->toBe(1)
        ->and(AuditLog::commit())->toBe(0)
        ->and(AuditLog::recorded('document.publish'))->toHaveCount(1);
});

it('can fake multiple recorded audit logs', function (): void {
    AuditLog::fake();

    AuditLog::recordMany([
        new AuditLogData(
            actor: new AuditLogActorData(type: 'user', id: 'member_123'),
            event: 'document.publish',
            tenantId: 'org_123',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(type: 'user', id: 'member_123'),
            event: 'document.publish',
            tenantId: 'org_123',
        ),
    ]);

    AuditLog::assertRecordedTimes('document.publish', 2);

    $logs = AuditLog::recorded('document.publish')->values();

    expect(Str::isUuid((string) $logs[0]->batchId))->toBeTrue()
        ->and($logs[1]->batchId)->toBe($logs[0]->batchId);
});
