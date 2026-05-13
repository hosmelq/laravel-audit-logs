<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use HosmelQ\AuditLog\Contracts\AuditLogManager as AuditLogManagerContract;
use HosmelQ\AuditLog\Contracts\AuditLogRepository;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Jobs\CommitAuditLogs;
use HosmelQ\AuditLog\Models\AuditLog as AuditLogModel;
use HosmelQ\AuditLog\Support\Config;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DatabaseAuditLogManager implements AuditLogManagerContract
{
    /**
     * @var class-string<AuditLogModel>
     */
    protected static string $model = AuditLogModel::class;

    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly AuditLogRepository $repository,
        private readonly AuditLogWriter $writer,
    ) {
    }

    /**
     * @return class-string<AuditLogModel>
     */
    public static function model(): string
    {
        return static::$model;
    }

    public static function newId(): string
    {
        $model = static::$model;

        return (new $model())->newUniqueId();
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

        static::$model = $model;
    }

    public function commit(): int
    {
        $logs = $this->repository->all();

        if ($logs === []) {
            return 0;
        }

        if (Config::queue()) {
            $job = (new CommitAuditLogs($logs))
                ->onConnection(Config::queueConnection())
                ->onQueue(Config::queueName());

            $this->dispatcher->dispatch($job);
        } else {
            $this->writer->write($logs);
        }

        $this->repository->flush();

        return count($logs);
    }

    public function record(AuditLogData $log): void
    {
        $log->id ??= static::newId();

        $this->repository->add($log);
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
