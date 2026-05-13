<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use Closure;
use HosmelQ\AuditLog\Contracts\AuditLogManager;
use HosmelQ\AuditLog\Data\AuditLogData;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

class AuditLogFake implements AuditLogManager
{
    /**
     * @var list<AuditLogData>
     */
    protected array $pending = [];

    /**
     * @var list<AuditLogData>
     */
    protected array $recorded = [];

    public function assertNothingRecorded(): void
    {
        PHPUnit::assertEmpty(
            $this->recorded,
            'Audit logs were recorded unexpectedly.',
        );
    }

    public function assertNotRecorded(Closure|string $callback): void
    {
        PHPUnit::assertTrue(
            $this->recorded($callback)->isEmpty(),
            'The unexpected audit log was recorded.',
        );
    }

    public function assertRecorded(Closure|string $callback): void
    {
        PHPUnit::assertTrue(
            $this->recorded($callback)->isNotEmpty(),
            'The expected audit log was not recorded.',
        );
    }

    public function assertRecordedTimes(Closure|string $callback, int $times = 1): void
    {
        PHPUnit::assertCount(
            $times,
            $this->recorded($callback),
            sprintf('The expected audit log was not recorded %d times.', $times),
        );
    }

    public function commit(): int
    {
        $count = count($this->pending);

        $this->pending = [];

        return $count;
    }

    public function record(AuditLogData $log): void
    {
        $log->id ??= DatabaseAuditLogManager::newId();

        $this->pending[] = $log;
        $this->recorded[] = $log;
    }

    /**
     * @return Collection<int, AuditLogData>
     */
    public function recorded(null|Closure|string $callback = null): Collection // @phpstan-ignore typePerfect.narrowReturnObjectType
    {
        $callback ??= fn (): bool => true;

        return Collection::make($this->recorded)->filter(
            fn (AuditLogData $log): bool => is_string($callback)
                ? $log->event === $callback
                : $callback($log) === true,
        );
    }

    /**
     * @param iterable<AuditLogData> $logs
     */
    public function recordMany(iterable $logs): void
    {
        $logs = $this->logs($logs);
        $batchId = count($logs) > 1 ? (string) Str::uuid() : null;

        foreach ($logs as $log) {
            $log->batchId ??= $batchId;

            $this->record($log);
        }
    }

    /**
     * @param iterable<AuditLogData> $logs
     *
     * @return list<AuditLogData>
     */
    private function logs(iterable $logs): array
    {
        if (is_array($logs)) {
            return array_values($logs);
        }

        $values = [];

        foreach ($logs as $log) {
            $values[] = $log;
        }

        return $values;
    }
}
