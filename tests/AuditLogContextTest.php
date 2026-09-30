<?php

declare(strict_types=1);

use HosmelQ\AuditLog\AuditLogContext;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Support\AuditLogIdentity;
use HosmelQ\AuditLog\Tests\TestSupport\TestUser;

it('propagates actor resolver failures', function (): void {
    $context = resolve(AuditLogContext::class);

    AuditLogContext::resolveActorUsing(fn () => throw new RuntimeException('Actor context failed'));

    expect(fn () => $context->actor())->toThrow(RuntimeException::class, 'Actor context failed');
});

it('propagates tenant resolver failures', function (): void {
    $context = resolve(AuditLogContext::class);

    AuditLogContext::resolveTenantUsing(fn () => throw new RuntimeException('Tenant context failed'));

    expect(fn () => $context->tenant())->toThrow(RuntimeException::class, 'Tenant context failed');
});

it('uses system context when resolvers return no identity', function (): void {
    $context = resolve(AuditLogContext::class);

    AuditLogContext::resolveActorUsing(fn (): null => null);
    AuditLogContext::resolveTenantUsing(fn (): null => null);

    expect($context->actor()->toArray())->toBe([
        'id' => 'system', 'metadata' => [], 'name' => 'System', 'type' => 'system',
    ])->and($context->tenant())->toBe('');
});

it('resolves each supported actor identity', function (AuditLogActorData|AuditLogIdentity|TestUser $actor): void {
    $context = resolve(AuditLogContext::class);

    AuditLogContext::resolveActorUsing(fn (): AuditLogActorData|AuditLogIdentity|TestUser => $actor);

    expect($context->actor()->id)->toBe('user-1');
})->with([
    'data' => [new AuditLogActorData(id: 'user-1', type: 'user')],
    'identity' => [new AuditLogIdentity(id: 'user-1', type: 'user')],
    'object' => [new TestUser()],
]);

it('uses a fresh context instance for each application scope', function (): void {
    AuditLogContext::resolveTenantUsing(fn (): int => 10);

    $context = resolve(AuditLogContext::class);

    expect($context->tenant())->toBe('10');

    app()->forgetScopedInstances();

    $next = resolve(AuditLogContext::class);

    expect($next)->not->toBe($context)
        ->and($next->tenant())->toBe('10');
});

it('evaluates resolvers each time without caching their results', function (): void {
    $context = resolve(AuditLogContext::class);
    $tenant = 10;

    AuditLogContext::resolveTenantUsing(function () use (&$tenant): int {
        return $tenant;
    });

    expect($context->tenant())->toBe('10');

    $tenant = 20;

    expect($context->tenant())->toBe('20');
});
