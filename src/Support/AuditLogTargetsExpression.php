<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Support;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;

final readonly class AuditLogTargetsExpression implements Expression
{
    /**
     * @param 'json_each'|'openjson' $function
     */
    public function __construct(private string $column, private string $function)
    {
    }

    public function getValue(Grammar $grammar): string
    {
        return $this->function.'('.$grammar->wrap($this->column).') as '.$grammar->wrapTable('audit_log_targets');
    }
}
