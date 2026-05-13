<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

use Carbon\CarbonImmutable;
use HosmelQ\AuditLog\Models\AuditLog as AuditLogModel;
use Illuminate\Support\Facades\DB;

it('prunes expired audit logs', function (): void {
    DB::table('audit_logs')->insert([
        auditLogRow(
            id: '01J0000000000000000000000J',
            expiresAt: CarbonImmutable::now()->subDay(),
        ),
        auditLogRow(
            id: '01J0000000000000000000000K',
            expiresAt: CarbonImmutable::now()->addDay(),
        ),
        auditLogRow(
            id: '01J0000000000000000000000L',
            expiresAt: null,
        ),
    ]);

    artisan('model:prune', [
        '--model' => [AuditLogModel::class],
    ])->assertExitCode(0);

    expect(DB::table('audit_logs')->where('id', '01J0000000000000000000000J')->doesntExist())->toBeTrue()
        ->and(DB::table('audit_logs')->where('id', '01J0000000000000000000000K')->exists())->toBeTrue()
        ->and(DB::table('audit_logs')->where('id', '01J0000000000000000000000L')->exists())->toBeTrue();
});

/**
 * @return array<string, null|string>
 */
function auditLogRow(string $id, null|CarbonImmutable $expiresAt): array
{
    $now = CarbonImmutable::now()->format('Y-m-d H:i:s.v');

    return [
        'actor_id' => 'member_123',
        'actor_name' => null,
        'actor_properties' => '{}',
        'actor_type' => 'user',
        'batch_id' => null,
        'bucket' => 'audit_logs',
        'description' => 'Published document',
        'event' => 'document.publish',
        'expires_at' => $expiresAt?->format('Y-m-d H:i:s.v'),
        'id' => $id,
        'inserted_at' => $now,
        'occurred_at' => $now,
        'properties' => '{}',
        'remote_ip' => null,
        'source' => 'platform',
        'targets' => '[]',
        'tenant_id' => 'org_123',
        'user_agent' => null,
    ];
}
