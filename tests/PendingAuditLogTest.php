<?php

declare(strict_types=1);

use function HosmelQ\AuditLog\audit_log;

use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\PendingAuditLog;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogActorType;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogBucket;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogEvent;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogSource;
use HosmelQ\AuditLog\Tests\TestSupport\AuditLogTargetType;
use Illuminate\Support\Facades\Config;

it('builds an audit log with defaults', function (): void {
    Config::set('audit-log.defaults.bucket', 'default_bucket');
    Config::set('audit-log.defaults.source', 'default_source');

    $log = PendingAuditLog::make('document.publish')->toAuditLogData();

    expect($log)
        ->bucket->toBe('default_bucket')
        ->event->toBe('document.publish')
        ->source->toBe('default_source')
        ->targets->toBe([])
        ->tenantId->toBe('')
        ->and($log->actor)
        ->id->toBe('system')
        ->name->toBe('System')
        ->type->toBe('system');
});

it('builds an audit log with explicit values', function (): void {
    $occurredAt = CarbonImmutable::createFromTimestampMsUTC(1_700_000_000_123);

    $log = PendingAuditLog::make('document.publish')
        ->tenant('org_123')
        ->actor(type: 'user', id: 'member_123', name: 'Hosmel Quintana', properties: ['team' => 'development'])
        ->batchId('batch_123')
        ->bucket('custom_bucket')
        ->description('Published document')
        ->id('01J00000000000000000000001')
        ->properties(['request_id' => 'req_123'])
        ->remoteIp('127.0.0.1')
        ->source('customer')
        ->target(type: 'document', id: 'doc_123', name: 'Quarterly report', properties: ['folder' => 'reports'])
        ->occurredAt($occurredAt)
        ->userAgent('Pest')
        ->toAuditLogData();

    expect($log)
        ->batchId->toBe('batch_123')
        ->bucket->toBe('custom_bucket')
        ->description->toBe('Published document')
        ->id->toBe('01J00000000000000000000001')
        ->occurredAt->toBe($occurredAt)
        ->properties->toBe(['request_id' => 'req_123'])
        ->remoteIp->toBe('127.0.0.1')
        ->source->toBe('customer')
        ->tenantId->toBe('org_123')
        ->userAgent->toBe('Pest')
        ->and($log->actor)
        ->id->toBe('member_123')
        ->name->toBe('Hosmel Quintana')
        ->properties->toBe(['team' => 'development'])
        ->type->toBe('user')
        ->and($log->targets())->toBe([
            [
                'type' => 'document',
                'id' => 'doc_123',
                'name' => 'Quarterly report',
                'properties' => ['folder' => 'reports'],
            ],
        ]);
});

it('builds an audit log with backed enums', function (): void {
    $pending = audit_log(AuditLogEvent::DocumentPublished);

    expect($pending)->toBeInstanceOf(PendingAuditLog::class);

    $log = $pending
        ->actor(type: AuditLogActorType::User, id: 'member_123')
        ->bucket(AuditLogBucket::Customer)
        ->source(AuditLogSource::Customer)
        ->target(type: AuditLogTargetType::Document, id: 'doc_123')
        ->toAuditLogData();

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
