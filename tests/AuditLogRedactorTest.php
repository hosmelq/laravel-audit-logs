<?php

declare(strict_types=1);

use HosmelQ\AuditLog\AuditLogRedactor;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogChangesData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use Illuminate\Support\Facades\Config;

it('rejects redaction keys that are not strings', function (string $option): void {
    Config::set('audit-log.redaction.'.$option, ['password', 123]);

    expect(fn () => resolve(AuditLogRedactor::class)->metadata(['password' => 'secret']))
        ->toThrow(InvalidArgumentException::class, 'Expected string keys.');
})->with(['exclude', 'mask']);

it('excludes and masks keys across event actor and target metadata', function (): void {
    Config::set('audit-log.redaction.exclude', ['password', 'token']);
    Config::set('audit-log.redaction.mask', ['email', 'token']);
    Config::set('audit-log.redaction.replacement', 'hidden');

    $metadata = ['password' => 'secret', 'token' => 'secret', 'email' => 'user@example.com', 'enabled' => false, 'count' => 0, 'optional' => null, 'empty' => ''];
    $log = new AuditLogData(
        actor: new AuditLogActorData(id: 'user-1', metadata: $metadata, name: 'Jane Doe', type: 'user'),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
        correlationId: 'correlation-1',
        metadata: $metadata,
        targets: [new AuditLogTargetData(id: 'account-1', metadata: $metadata, type: 'account')],
        tenantId: 'tenant-1',
    );

    $redacted = resolve(AuditLogRedactor::class)->redact($log);
    $expected = ['email' => 'hidden', 'enabled' => false, 'count' => 0, 'optional' => null, 'empty' => ''];

    expect($redacted->metadata)->toBe($expected)
        ->and($redacted->actor->metadata)->toBe($expected)
        ->and($redacted->targets[0]->metadata)->toBe($expected)
        ->and($redacted->id)->toBe($log->id)
        ->and($redacted->occurredAt->equalTo($log->occurredAt))->toBeTrue()
        ->and($redacted->correlationId)->toBe('correlation-1')
        ->and($redacted->tenantId)->toBe('tenant-1')
        ->and($redacted->actor->name)->toBe('Jane Doe')
        ->and($log->metadata)->toBe($metadata)
        ->and($log->actor->metadata)->toBe($metadata)
        ->and($log->targets[0]->metadata)->toBe($metadata);
});

it('redacts changes while preserving masked change records and absent attributes', function (): void {
    Config::set('audit-log.redaction.exclude', ['password']);
    Config::set('audit-log.redaction.mask', ['email', 'added']);

    $changes = AuditLogChangesData::between(
        before: ['password' => 'old', 'email' => 'old@example.com', 'removed' => null],
        after: ['password' => 'new', 'email' => 'new@example.com', 'added' => false],
    );
    $log = new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
        changes: $changes,
    );

    expect(resolve(AuditLogRedactor::class)->redact($log)->changes?->toArray())->toBe([
        'before' => ['email' => '[REDACTED]', 'removed' => null],
        'after' => ['email' => '[REDACTED]', 'added' => '[REDACTED]'],
    ])->and($changes->before['password'])->toBe('old');
});

it('matches metadata keys exactly and does not add missing masked keys', function (): void {
    Config::set('audit-log.redaction.exclude', ['password']);
    Config::set('audit-log.redaction.mask', ['email']);

    expect(resolve(AuditLogRedactor::class)->metadata(['Password' => 'value']))
        ->toBe(['Password' => 'value']);
});

it('preserves all metadata when no rules are configured', function (): void {
    expect(resolve(AuditLogRedactor::class)->metadata(['password' => 'value']))
        ->toBe(['password' => 'value']);
});
