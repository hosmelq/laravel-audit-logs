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
