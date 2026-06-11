<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use Illuminate\Support\Str;

class AuditLogId
{
    public function correlation(): string
    {
        return $this->new('cor');
    }

    public function log(): string
    {
        return $this->new('log');
    }

    private function new(string $prefix): string
    {
        return $prefix.'_'.Str::ulid();
    }
}
