<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use BackedEnum;
use Closure;
use HosmelQ\AuditLog\Contracts\AuditLogManager;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Support\Enum;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

final class AuditLogFake implements AuditLogManager
{
    /**
     * @var list<AuditLogData>
     */
    private array $recorded = [];

    public function __construct(private readonly AuditLogCorrelation $correlation = new AuditLogCorrelation())
    {
    }

    public function assertNothingRecorded(): void
    {
        PHPUnit::assertEmpty(
            $this->recorded,
            'Audit logs were recorded unexpectedly.',
        );
    }

    public function assertNotRecorded(BackedEnum|Closure|string $callback): void
    {
        PHPUnit::assertTrue(
            $this->recorded($callback)->isEmpty(),
            'The unexpected audit log was recorded.',
        );
    }

    public function assertRecorded(BackedEnum|Closure|string $callback): void
    {
        PHPUnit::assertTrue(
            $this->recorded($callback)->isNotEmpty(),
            'The expected audit log was not recorded.',
        );
    }

    public function assertRecordedInCorrelation(BackedEnum|string ...$events): void
    {
        if ($events === []) {
            PHPUnit::fail('Expected at least one audit log event for correlation assertion.');
        }

        $eventValues = array_values(array_unique(array_map(
            Enum::value(...),
            $events,
        )));

        $correlations = [];

        foreach ($this->recorded as $log) {
            if ($log->correlationId === null) {
                continue;
            }

            $correlations[$log->correlationId][$log->event] = true;
        }

        $matchingCorrelationId = null;

        foreach ($correlations as $correlationId => $correlatedEvents) {
            $containsEveryEvent = true;

            foreach ($eventValues as $event) {
                if (! isset($correlatedEvents[$event])) {
                    $containsEveryEvent = false;

                    break;
                }
            }

            if ($containsEveryEvent) {
                $matchingCorrelationId = $correlationId;

                break;
            }
        }

        PHPUnit::assertNotNull($matchingCorrelationId, sprintf(
            'The expected audit logs [%s] were not recorded with the same correlation id.',
            implode(', ', $eventValues),
        ));
    }

    public function assertRecordedTimes(BackedEnum|Closure|string $callback, int $times = 1): void
    {
        PHPUnit::assertCount(
            $times,
            $this->recorded($callback),
            sprintf('The expected audit log was not recorded %d times.', $times),
        );
    }

    /**
     * @template TReturn
     *
     * @param Closure(): TReturn $callback
     *
     * @return TReturn
     */
    public function correlate(Closure $callback, null|string $id = null): mixed
    {
        return $this->correlation->run($callback, $id);
    }

    /**
     * @param AuditLogData|iterable<AuditLogData> $logs
     */
    public function record(AuditLogData|iterable $logs): void
    {
        $logs = $this->logs($logs);

        if ($logs === []) {
            return;
        }

        $correlationId = $this->correlation->current();

        if ($correlationId === null && $this->shouldCorrelate($logs)) {
            $correlationId = resolve(AuditLogId::class)->correlation();
        }

        foreach ($logs as $log) {
            $log = $log->correlationId === null && $correlationId !== null
                ? $log->withCorrelationId($correlationId)
                : $log;

            $this->recorded[] = $log;
        }
    }

    /**
     * @return Collection<int, AuditLogData>
     */
    public function recorded(null|BackedEnum|Closure|string $callback = null): Collection
    {
        if ($callback === null) {
            return Collection::make($this->recorded);
        }

        if (! $callback instanceof Closure) {
            $event = Enum::value($callback);

            return Collection::make($this->recorded)->filter(
                fn (AuditLogData $log): bool => $log->event === $event,
            );
        }

        return Collection::make($this->recorded)->filter(
            fn (AuditLogData $log): bool => $callback($log) === true,
        );
    }

    /**
     * @param AuditLogData|iterable<AuditLogData> $logs
     *
     * @return list<AuditLogData>
     */
    private function logs(AuditLogData|iterable $logs): array
    {
        if ($logs instanceof AuditLogData) {
            return [$logs];
        }

        if (! is_array($logs)) {
            $values = [];

            foreach ($logs as $log) {
                $values[] = $log;
            }

            $logs = $values;
        }

        return array_values($logs);
    }

    /**
     * @param list<AuditLogData> $logs
     */
    private function shouldCorrelate(array $logs): bool
    {
        if (count($logs) < 2) {
            return false;
        }

        foreach ($logs as $log) {
            if ($log->correlationId === null) {
                return true;
            }
        }

        return false;
    }
}
