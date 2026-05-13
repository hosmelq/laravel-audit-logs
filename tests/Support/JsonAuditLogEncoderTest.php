<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use HosmelQ\AuditLog\Support\JsonAuditLogEncoder;

it('round trips a complete audit log payload', function (): void {
    $encoder = resolve(JsonAuditLogEncoder::class);

    $log = new AuditLogData(
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
        occurredAt: CarbonImmutable::createFromTimestampMsUTC(1_700_000_000_123),
        batchId: 'batch_123',
        id: '01J00000000000000000000010',
        remoteIp: '127.0.0.1',
        userAgent: 'Pest',
        bucket: 'audit_logs',
        description: 'Published document',
        event: 'document.publish',
        source: 'customer',
        tenantId: 'org_123',
    );

    $payload = $encoder->encode($log);
    $restored = $encoder->decode($payload);

    expect($restored)->toEqual($log)
        ->and($payload)->json()
        ->occurred_at->toBe('2023-11-14T22:13:20.123+00:00');
});
