<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Data\AuditLogRetentionData;

it('rejects negative retention days', function (): void {
    expect(fn (): AuditLogRetentionData => new AuditLogRetentionData(-1))
        ->toThrow(InvalidArgumentException::class, 'Audit log retention days must be a non-negative integer or null.');
});

it('preserves explicit retention periods including zero and null', function (null|int $days): void {
    expect((new AuditLogRetentionData($days))->days)->toBe($days);
})->with(['indefinite' => [null], 'immediate' => [0], 'period' => [90]]);
