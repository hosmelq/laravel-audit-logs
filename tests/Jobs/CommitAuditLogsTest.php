<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\AuditLogWriter;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Jobs\CommitAuditLogs;
use Illuminate\Support\Facades\DB;

it('writes audit logs after serializing the job payload', function (): void {
    $job = new CommitAuditLogs([
        new AuditLogData(
            actor: new AuditLogActorData(
                type: 'user',
                id: 'member_123',
                name: 'Hosmel Quintana',
            ),
            occurredAt: CarbonImmutable::createFromTimestampMsUTC(1_700_000_000_123),
            id: '01J00000000000000000000004',
            description: 'Published document',
            event: 'document.publish',
            tenantId: 'org_123',
        ),
    ]);

    $restored = unserialize(serialize($job));

    expect($restored)->toBeInstanceOf(CommitAuditLogs::class);

    $restored->handle(resolve(AuditLogWriter::class));

    $row = DB::table('audit_logs')->where('id', '01J00000000000000000000004')->first();

    expect($row)
        ->event->toBe('document.publish')
        ->id->toBe('01J00000000000000000000004')
        ->occurred_at->toBe('2023-11-14 22:13:20.123')
        ->tenant_id->toBe('org_123');
});
