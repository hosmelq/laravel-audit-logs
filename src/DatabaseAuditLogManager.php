<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use Closure;
use HosmelQ\AuditLog\Contracts\AuditLogManager as AuditLogManagerContract;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Models\AuditLog as AuditLogModel;
use InvalidArgumentException;

final class DatabaseAuditLogManager implements AuditLogManagerContract
{
    /**
     * @var class-string<AuditLogModel>
     */
    private static string $model = AuditLogModel::class;

    public function __construct(
        private readonly AuditLogCorrelation $correlation,
        private readonly AuditLogWriter $writer,
    ) {
    }

    /**
     * @return class-string<AuditLogModel>
     */
    public static function model(): string
    {
        return self::$model;
    }

    /**
     * @param class-string<AuditLogModel> $model
     */
    public static function useModel(string $model): void
    {
        if ($model !== AuditLogModel::class && ! is_subclass_of($model, AuditLogModel::class)) {
            throw new InvalidArgumentException(sprintf(
                'Audit log model [%s] must extend [%s].',
                $model,
                AuditLogModel::class,
            ));
        }

        self::$model = $model;
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

        /** @var list<AuditLogData> $records */
        $records = [];

        foreach ($logs as $log) {
            $records[] = $log->correlationId === null && $correlationId !== null
                ? $log->withCorrelationId($correlationId)
                : $log;
        }

        $this->writer->write($records);
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
