<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Models\AuditLog;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('matches target pairs on sqlite with a configured table and connection', function (string $prefix): void {
    Config::set('database.connections.audit_scope_sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => $prefix]);
    Config::set('audit-log.storage.connection', 'audit_scope_sqlite');
    Config::set('audit-log.storage.table', 'scope_audit_logs');

    DB::connection('audit_scope_sqlite')->getSchemaBuilder()->create('scope_audit_logs', function (Blueprint $table): void {
        $table->string('id')->primary();
        $table->json('targets');
    });

    foreach ([
        'matching' => [['id' => 'other', 'type' => 'account'], ['id' => '0', 'type' => 'document', 'name' => 'Draft', 'metadata' => []]],
        'split-pair' => [['id' => 'other', 'type' => 'document'], ['id' => '0', 'type' => 'account']],
        'empty' => [],
    ] as $id => $targets) {
        AuditLog::create(['id' => $id, 'targets' => $targets]);
    }

    expect(AuditLog::query()->forTarget('document', 0)->pluck('id')->all())->toBe(['matching']);
})->with(['prefixed tables' => 'app_', 'unprefixed tables' => '']);

it('compiles parameterized target containment for postgres', function (): void {
    DB::extend('audit_scope_pgsql', fn (): PostgresConnection => new PostgresConnection(
        fn () => throw new RuntimeException('This connection only compiles queries.'),
        config: ['driver' => 'pgsql'],
    ));

    Config::set('database.connections.audit_scope_pgsql', ['driver' => 'audit_scope_pgsql']);
    Config::set('audit-log.storage.connection', 'audit_scope_pgsql');

    $query = AuditLog::query()->forTarget('document', "doc'123");

    expect($query->toSql())->toContain('("audit_logs"."targets")::jsonb @> ?')
        ->and($query->getBindings())->toBe(['[{"id":"doc\'123","type":"document"}]']);
});

it('compiles parameterized target row queries for sql server', function (string $prefix): void {
    DB::extend('audit_scope_sqlsrv', fn (): SqlServerConnection => new SqlServerConnection(
        fn () => throw new RuntimeException('This connection only compiles queries.'),
        tablePrefix: $prefix,
        config: ['driver' => 'sqlsrv'],
    ));

    Config::set('database.connections.audit_scope_sqlsrv', ['driver' => 'audit_scope_sqlsrv']);
    Config::set('audit-log.storage.connection', 'audit_scope_sqlsrv');

    $query = AuditLog::query()->forTarget("doc'ument", "doc'123");

    expect($query->toSql())->toContain('openjson(['.$prefix.'audit_logs].[targets]) as ['.$prefix.'audit_log_targets]', 'json_value(['.$prefix.'audit_log_targets].[value]')
        ->and($query->getBindings())->toBe(["doc'ument", "doc'123"]);
})->with(['prefixed tables' => 'app_', 'unprefixed tables' => '']);
