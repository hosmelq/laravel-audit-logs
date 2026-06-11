<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Models\AuditLog;
use Illuminate\Support\Facades\Date;

it('only prunes expired audit logs', function (): void {
    AuditLog::create([
        'actor_id' => 'user-1',
        'actor_metadata' => [],
        'actor_type' => 'user',
        'description' => '',
        'event' => 'audit.expired',
        'expires_at' => Date::now()->subSecond(),
        'id' => 'log_01HX0000000000000000000000',
        'inserted_at' => Date::now(),
        'occurred_at' => Date::now(),
        'metadata' => [],
        'targets' => [],
        'tenant_id' => 'tenant-1',
    ]);

    AuditLog::create([
        'actor_id' => 'user-1',
        'actor_metadata' => [],
        'actor_type' => 'user',
        'description' => '',
        'event' => 'audit.active',
        'expires_at' => Date::now()->addDay(),
        'id' => 'log_01HX0000000000000000000001',
        'inserted_at' => Date::now(),
        'occurred_at' => Date::now(),
        'metadata' => [],
        'targets' => [],
        'tenant_id' => 'tenant-1',
    ]);

    expect((new AuditLog())->prunable()->pluck('event')->all())->toBe(['audit.expired']);
});
