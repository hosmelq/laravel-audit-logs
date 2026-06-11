<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use Closure;
use Illuminate\Support\Str;

final class AuditLogCorrelation
{
    /**
     * @var list<string>
     */
    private array $stack = [];

    public function current(): null|string
    {
        if ($this->stack === []) {
            return null;
        }

        return $this->stack[array_key_last($this->stack)];
    }

    /**
     * @template TReturn
     *
     * @param Closure(): TReturn $callback
     *
     * @return TReturn
     */
    public function run(Closure $callback, null|string $id = null): mixed
    {
        if ($id !== null && Str::trim($id) === '') {
            return $callback();
        }

        $this->stack[] = $id ?? resolve(AuditLogId::class)->correlation();

        try {
            return $callback();
        } finally {
            array_pop($this->stack);
        }
    }
}
