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

it('reads retention days from environment values', function (string $value, null|int $days): void {
    $_SERVER['AUDIT_LOG_RETENTION_DAYS'] = $value;

    try {
        Config::set('audit-log', require __DIR__.'/../../config/audit-log.php');
    } finally {
        unset($_SERVER['AUDIT_LOG_RETENTION_DAYS']);
    }

    expect(AuditLogConfig::retentionDays())->toBe($days);
})->with([
    'empty' => ['', null],
    'zero' => ['0', 0],
    'days' => ['30', 30],
    'zeros only' => ['00', 0],
    'leading zero' => ['030', 30],
    'plus sign' => ['+30', 30],
    'leading non-breaking space' => ["\u{00A0}030", 30],
]);

it('throws for invalid retention environment values', function (string $value): void {
    $_SERVER['AUDIT_LOG_RETENTION_DAYS'] = $value;

    try {
        Config::set('audit-log', require __DIR__.'/../../config/audit-log.php');
    } finally {
        unset($_SERVER['AUDIT_LOG_RETENTION_DAYS']);
    }

    expect(fn (): null|int => AuditLogConfig::retentionDays())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('Invalid audit-log.retention.days value [%s].', $value),
        );
})->with([
    'words' => ['thirty'],
    'sign alone' => ['+'],
    'negative' => ['-30'],
    'fraction' => ['1.5'],
    'overflow' => ['9223372036854775808'],
]);
