<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Support\AuditLogTargetsExpression;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Illuminate\Support\Facades\DB;

it('escapes configured identifiers in sqlite target row expressions', function (): void {
    $grammar = new SQLiteGrammar(DB::connection());
    $expression = new AuditLogTargetsExpression('audit"logs.targets', 'json_each');

    expect($expression->getValue($grammar))->toBe('json_each("audit""logs"."targets") as "audit_log_targets"');
});

it('escapes configured identifiers in sql server target row expressions', function (): void {
    $grammar = new SqlServerGrammar(DB::connection());
    $expression = new AuditLogTargetsExpression('audit]logs.targets', 'openjson');

    expect($expression->getValue($grammar))->toBe('openjson([audit]]logs].[targets]) as [audit_log_targets]');
});
