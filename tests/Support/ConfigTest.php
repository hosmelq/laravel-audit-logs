<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Support\Config as AuditLogConfig;
use Illuminate\Support\Facades\Config;

it('reads the storage connection name or null', function (null|string $connection): void {
    Config::set('audit-log.storage.connection', $connection);

    expect(AuditLogConfig::storageConnection())->toBe($connection);
})->with([null, 'audit']);

it('throws for invalid storage connections', function (): void {
    Config::set('audit-log.storage.connection', false);

    expect(fn (): null|string => AuditLogConfig::storageConnection())
        ->toThrow(
            InvalidArgumentException::class,
            'Invalid audit-log.storage.connection value. Expected a connection name or null.',
        );
});

it('throws for negative retention days', function (): void {
    Config::set('audit-log.retention.days', -1);

    expect(fn (): null|int => AuditLogConfig::retentionDays())
        ->toThrow(InvalidArgumentException::class, 'Invalid audit-log.retention.days value [-1].');
});

it('throws for invalid storage insert chunk sizes', function (): void {
    Config::set('audit-log.storage.insert_chunk_size', 0);

    expect(fn (): int => AuditLogConfig::storageInsertChunkSize())
        ->toThrow(
            InvalidArgumentException::class,
            'Invalid audit-log.storage.insert_chunk_size value.',
        );
});
