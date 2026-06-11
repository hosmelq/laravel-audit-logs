<?php

declare(strict_types=1);

use function HosmelQ\AuditLog\audit_log;

use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\Contracts\AuditLogManager;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;
use HosmelQ\AuditLog\Tests\TestSupport\TestEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

it('returns the audit log manager when called without an event', function (): void {
    expect(audit_log())->toBe(resolve(AuditLogManager::class));
});

it('builds audit logs with request metadata', function (): void {
    Config::set('audit-log.request.capture_in_console', true);

    app()->instance('request', Request::create('/', 'GET', server: [
        'HTTP_USER_AGENT' => 'Browser',
        'REMOTE_ADDR' => '203.0.113.10',
    ]));

    $log = audit_log('account.closed')->toAuditLogData();

    expect($log)
        ->remoteIp->toBe('203.0.113.10')
        ->userAgent->toBe('Browser');
});

it('builds audit logs with fluent attributes', function (): void {
    $occurredAt = CarbonImmutable::parse('2026-05-25 10:00:00.123 UTC');

    $log = audit_log(TestEvent::AccountUpdated)
        ->actor('user', 'user-1')
        ->bucket('security')
        ->correlationId('correlation-1')
        ->description('Account updated.')
        ->id('log-1')
        ->occurredAt($occurredAt)
        ->metadata(['status' => 'active'])
        ->remoteIp('127.0.0.1')
        ->source('tests')
        ->target('account', 'account-1')
        ->tenant('tenant-1')
        ->userAgent('Browser')
        ->toAuditLogData();

    expect($log)
        ->actor->id->toBe('user-1')
        ->bucket->toBe('security')
        ->correlationId->toBe('correlation-1')
        ->description->toBe('Account updated.')
        ->event->toBe('account.updated')
        ->id->toBe('log-1')
        ->occurredAt->equalTo($occurredAt)->toBeTrue()
        ->metadata->toBe(['status' => 'active'])
        ->remoteIp->toBe('127.0.0.1')
        ->source->toBe('tests')
        ->targets()->{0}->id->toBe('account-1')
        ->tenantId->toBe('tenant-1')
        ->userAgent->toBe('Browser');
});

it('builds and records audit logs with configured defaults', function (): void {
    AuditLog::fake();

    Config::set('audit-log.defaults.bucket', 'default-bucket');
    Config::set('audit-log.defaults.source', 'default-source');

    audit_log('account.closed')->record();

    AuditLog::assertRecorded(fn (AuditLogData $log): bool => $log->bucket === 'default-bucket'
        && $log->event === 'account.closed'
        && $log->source === 'default-source');
});
