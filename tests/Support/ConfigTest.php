<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Support\Config as AuditLogConfig;
use Illuminate\Support\Facades\Config;

it('throws for negative retention days', function (): void {
    Config::set('audit-log.retention.days', -1);

    expect(fn (): null|int => AuditLogConfig::retentionDays())
        ->toThrow(InvalidArgumentException::class, 'Invalid audit-log.retention.days value [-1].');
});

it('throws for invalid storage insert chunk sizes', function (): void {
    Config::set('audit-log.storage.insert_chunk_size', 0);

    expect(fn (): int => AuditLogConfig::storageInsertChunkSize())
        ->toThrow(InvalidArgumentException::class, 'Invalid audit-log.storage.insert_chunk_size value.');
});

it('rejects invalid event retention periods', function (bool|float|int|string $days): void {
    Config::set('audit-log.retention.events', ['account.updated' => $days]);

    expect(fn (): null|int => AuditLogConfig::retentionDays(event: 'account.updated', bucket: 'security'))
        ->toThrow(InvalidArgumentException::class, 'Invalid audit-log.retention.events.account.updated value');
})->with(['negative' => [-1], 'string' => ['30'], 'float' => [1.5], 'boolean' => [false]]);

it('rejects invalid bucket retention periods', function (): void {
    Config::set('audit-log.retention.buckets', ['security' => -1]);

    expect(fn (): null|int => AuditLogConfig::retentionDays(event: 'account.updated', bucket: 'security'))
        ->toThrow(InvalidArgumentException::class, 'Invalid audit-log.retention.buckets.security value [-1].');
});

it('rejects global retention periods that are not integers or null', function (): void {
    Config::set('audit-log.retention.days', '30');

    expect(fn (): null|int => AuditLogConfig::retentionDays())
        ->toThrow(InvalidArgumentException::class, 'Invalid audit-log.retention.days value.');
});

it('uses event rules before bucket rules and the global period', function (): void {
    Config::set('audit-log.retention.days', 30);
    Config::set('audit-log.retention.events', ['account.updated' => 90]);
    Config::set('audit-log.retention.buckets', ['security' => 60]);

    expect(AuditLogConfig::retentionDays('account.updated', 'security'))->toBe(90)
        ->and(AuditLogConfig::retentionDays('account.closed', 'security'))->toBe(60)
        ->and(AuditLogConfig::retentionDays('account.closed', 'application'))->toBe(30)
        ->and(AuditLogConfig::retentionDays())->toBe(30);
});

it('preserves null and zero event rules without falling through', function (null|int $days): void {
    Config::set('audit-log.retention.days', 30);
    Config::set('audit-log.retention.events', ['account.updated' => $days]);
    Config::set('audit-log.retention.buckets', ['security' => 60]);

    expect(AuditLogConfig::retentionDays('account.updated', 'security'))->toBe($days);
})->with(['indefinite' => [null], 'immediate' => [0]]);

it('preserves null and zero bucket rules without falling through', function (null|int $days): void {
    Config::set('audit-log.retention.days', 30);
    Config::set('audit-log.retention.buckets', ['security' => $days]);

    expect(AuditLogConfig::retentionDays('account.updated', 'security'))->toBe($days);
})->with(['indefinite' => [null], 'immediate' => [0]]);
