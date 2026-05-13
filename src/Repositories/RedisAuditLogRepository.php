<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Repositories;

use HosmelQ\AuditLog\Contracts\AuditLogRepository;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Support\Config;
use HosmelQ\AuditLog\Support\JsonAuditLogEncoder;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\Connection;

readonly class RedisAuditLogRepository implements AuditLogRepository
{
    public function __construct(
        private Factory $redis,
        private JsonAuditLogEncoder $encoder,
    ) {
    }

    public function add(AuditLogData $log): void
    {
        $connection = $this->connection();
        $key = Config::redisKey();

        $connection->command('rpush', [$key, $this->encoder->encode($log)]);
        $connection->command('expire', [$key, Config::redisTtl()]);
    }

    /**
     * @return list<AuditLogData>
     */
    public function all(): array
    {
        $connection = $this->connection();
        $pendingKey = Config::redisKey();
        $processingKey = $this->processingKey();

        if (! $this->exists($connection, $processingKey) && $this->exists($connection, $pendingKey)) {
            $connection->command('rename', [$pendingKey, $processingKey]);
        }

        $payloads = $connection->command('lrange', [$processingKey, 0, -1]);

        if (! is_array($payloads)) {
            return [];
        }

        $logs = [];

        foreach ($payloads as $payload) {
            if (is_string($payload)) {
                $logs[] = $this->encoder->decode($payload);
            }
        }

        return $logs;
    }

    public function flush(): void
    {
        $this->connection()->command('del', [$this->processingKey()]);
    }

    private function connection(): Connection
    {
        return $this->redis->connection(Config::redisConnection());
    }

    private function exists(Connection $connection, string $key): bool
    {
        $exists = $connection->command('exists', [$key]);

        return is_int($exists) ? $exists > 0 : $exists === true;
    }

    private function processingKey(): string
    {
        return Config::redisKey().':processing';
    }
}
