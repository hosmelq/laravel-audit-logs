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

it('reads request capture options from boolean environment values', function (
    string $enabled,
    string $disabled,
): void {
    $_SERVER['AUDIT_LOG_REQUEST_CAPTURE_IN_CONSOLE'] = $enabled;
    $_SERVER['AUDIT_LOG_REQUEST_CAPTURE_REMOTE_IP'] = $disabled;
    $_SERVER['AUDIT_LOG_REQUEST_CAPTURE_USER_AGENT'] = $disabled;

    try {
        Config::set('audit-log', require __DIR__.'/../../config/audit-log.php');
    } finally {
        unset(
            $_SERVER['AUDIT_LOG_REQUEST_CAPTURE_IN_CONSOLE'],
            $_SERVER['AUDIT_LOG_REQUEST_CAPTURE_REMOTE_IP'],
            $_SERVER['AUDIT_LOG_REQUEST_CAPTURE_USER_AGENT'],
        );
    }

    expect(AuditLogConfig::requestCaptureInConsole())->toBeTrue()
        ->and(AuditLogConfig::requestCaptureRemoteIp())->toBeFalse()
        ->and(AuditLogConfig::requestCaptureUserAgent())->toBeFalse();
})->with([
    'booleans' => ['true', 'false'],
    'numbers' => ['1', '0'],
    'on and off' => ['on', 'off'],
    'yes and no' => ['yes', 'no'],
]);

it('throws for invalid request capture environment values', function (
    string $variable,
    string $option,
    string $value,
): void {
    $_SERVER[$variable] = $value;

    try {
        Config::set('audit-log', require __DIR__.'/../../config/audit-log.php');
    } finally {
        unset($_SERVER[$variable]);
    }

    expect(fn (): bool => match ($option) {
        'capture_in_console' => AuditLogConfig::requestCaptureInConsole(),
        'capture_remote_ip' => AuditLogConfig::requestCaptureRemoteIp(),
        default => AuditLogConfig::requestCaptureUserAgent(),
    })->toThrow(
        InvalidArgumentException::class,
        sprintf('Invalid audit-log.request.%s value. Expected a boolean.', $option),
    );
})->with([
    'console' => ['AUDIT_LOG_REQUEST_CAPTURE_IN_CONSOLE', 'capture_in_console'],
    'remote ip' => ['AUDIT_LOG_REQUEST_CAPTURE_REMOTE_IP', 'capture_remote_ip'],
    'user agent' => ['AUDIT_LOG_REQUEST_CAPTURE_USER_AGENT', 'capture_user_agent'],
])->with([
    'empty' => [''],
    'null' => ['(null)'],
    'unknown' => ['maybe'],
]);
