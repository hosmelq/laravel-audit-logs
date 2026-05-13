<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogActorType;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogBucket;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogEvent;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogEventCode;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogSource;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogTargetType;

it('creates an audit log from required attributes', function (): void {
    $log = AuditLogData::fromArray([
        'actor' => [
            'type' => 'user',
            'id' => 'member_123',
        ],
        'description' => '',
        'event' => 'document.publish',
        'tenant_id' => 'org_123',
    ]);

    expect($log->toArray())
        ->actor->toBe([
            'type' => 'user',
            'id' => 'member_123',
            'name' => null,
            'properties' => [],
        ])
        ->batch_id->toBeNull()
        ->bucket->toBe('audit_logs')
        ->description->toBe('')
        ->event->toBe('document.publish')
        ->id->toBeNull()
        ->properties->toBe([])
        ->remote_ip->toBeNull()
        ->source->toBe('platform')
        ->targets->toBe([])
        ->tenant_id->toBe('org_123')
        ->user_agent->toBeNull();
});

it('creates an audit log from all attributes', function (): void {
    $log = AuditLogData::fromArray([
        'actor' => [
            'type' => 'user',
            'id' => 'member_123',
            'name' => 'Hosmel Quintana',
            'properties' => ['team' => 'development'],
        ],
        'properties' => ['request_id' => 'req_123'],
        'targets' => [
            [
                'type' => 'document',
                'id' => 'doc_123',
                'name' => 'Quarterly report',
                'properties' => ['folder' => 'reports'],
            ],
        ],
        'occurred_at' => 1_700_000_000_123,
        'batch_id' => 'batch_123',
        'id' => '01J00000000000000000000001',
        'remote_ip' => '127.0.0.1',
        'user_agent' => 'Pest',
        'bucket' => 'custom_bucket',
        'description' => 'Published document',
        'event' => 'document.publish',
        'source' => 'customer',
        'tenant_id' => 'org_123',
    ]);

    expect($log->toArray())->toBe([
        'tenant_id' => 'org_123',
        'event' => 'document.publish',
        'description' => 'Published document',
        'actor' => [
            'type' => 'user',
            'id' => 'member_123',
            'name' => 'Hosmel Quintana',
            'properties' => ['team' => 'development'],
        ],
        'targets' => [
            [
                'type' => 'document',
                'id' => 'doc_123',
                'name' => 'Quarterly report',
                'properties' => ['folder' => 'reports'],
            ],
        ],
        'bucket' => 'custom_bucket',
        'source' => 'customer',
        'id' => '01J00000000000000000000001',
        'batch_id' => 'batch_123',
        'occurred_at' => '2023-11-14T22:13:20.123+00:00',
        'remote_ip' => '127.0.0.1',
        'user_agent' => 'Pest',
        'properties' => ['request_id' => 'req_123'],
    ]);
});

it('creates an audit log from backed enums', function (): void {
    $log = new AuditLogData(
        actor: new AuditLogActorData(type: AuditLogActorType::User, id: 'member_123'),
        targets: [
            new AuditLogTargetData(type: AuditLogTargetType::Document, id: 'doc_123'),
        ],
        bucket: AuditLogBucket::Customer,
        event: AuditLogEvent::DocumentPublished,
        source: AuditLogSource::Customer,
        tenantId: 'org_123',
    );

    expect($log)
        ->bucket->toBe('customer_events')
        ->event->toBe('document.publish')
        ->source->toBe('customer')
        ->and($log->actor)
        ->type->toBe('user')
        ->and($log->targets())->toBe([
            [
                'type' => 'document',
                'id' => 'doc_123',
                'name' => null,
                'properties' => [],
            ],
        ]);
});

it('creates an audit log from int backed enums', function (): void {
    $log = new AuditLogData(
        actor: new AuditLogActorData(type: AuditLogActorType::User, id: 'member_123'),
        event: AuditLogEventCode::DocumentPublished,
        tenantId: 'org_123',
    );

    expect($log)
        ->event->toBe('1001');
});

it('creates an audit log target from required attributes', function (): void {
    $log = AuditLogData::fromArray([
        'actor' => [
            'type' => 'user',
            'id' => 'member_123',
        ],
        'targets' => [
            [
                'type' => 'document',
                'id' => 'doc_123',
            ],
        ],
        'description' => '',
        'event' => 'document.publish',
        'tenant_id' => 'org_123',
    ]);

    expect($log->targets())->toBe([
        [
            'type' => 'document',
            'id' => 'doc_123',
            'name' => null,
            'properties' => [],
        ],
    ]);
});

it('creates an audit log target from all attributes', function (): void {
    $log = AuditLogData::fromArray([
        'actor' => [
            'type' => 'user',
            'id' => 'member_123',
        ],
        'targets' => [
            [
                'type' => 'document',
                'id' => 'doc_123',
                'name' => 'Quarterly report',
                'properties' => ['folder' => 'reports'],
            ],
        ],
        'description' => '',
        'event' => 'document.publish',
        'tenant_id' => 'org_123',
    ]);

    expect($log->targets())->toBe([
        [
            'type' => 'document',
            'id' => 'doc_123',
            'name' => 'Quarterly report',
            'properties' => ['folder' => 'reports'],
        ],
    ]);
});
