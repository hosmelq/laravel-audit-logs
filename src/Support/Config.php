<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Support;

use Illuminate\Support\Facades\Config as ConfigFacade;

class Config
{
    public static function autoCommit(): bool
    {
        return ConfigFacade::boolean('audit-log.auto_commit');
    }

    public static function defaultBucket(): string
    {
        return ConfigFacade::string('audit-log.defaults.bucket');
    }

    public static function defaultSource(): string
    {
        return ConfigFacade::string('audit-log.defaults.source');
    }

    public static function driver(): string
    {
        return ConfigFacade::string('audit-log.driver');
    }

    /**
     * @return positive-int
     */
    public static function insertChunkSize(): int
    {
        return max(1, ConfigFacade::integer('audit-log.database.insert_chunk_size'));
    }

    public static function logsTable(): string
    {
        return ConfigFacade::string('audit-log.database.logs_table');
    }

    public static function queue(): bool
    {
        return ConfigFacade::boolean('audit-log.queue.enabled');
    }

    public static function queueCommitAfterFailure(): bool
    {
        return ConfigFacade::boolean('audit-log.queue.commit_after_failure');
    }

    public static function queueConnection(): null|string
    {
        $connection = ConfigFacade::get('audit-log.queue.connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }

    public static function queueName(): null|string
    {
        $name = ConfigFacade::get('audit-log.queue.name');

        return is_string($name) && $name !== '' ? $name : null;
    }

    public static function redisConnection(): null|string
    {
        $connection = ConfigFacade::get('audit-log.redis.connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }

    public static function redisKey(): string
    {
        return ConfigFacade::string('audit-log.redis.key');
    }

    /**
     * @return positive-int
     */
    public static function redisTtl(): int
    {
        return max(1, ConfigFacade::integer('audit-log.redis.ttl'));
    }

    public static function retentionDays(): null|int
    {
        if (ConfigFacade::get('audit-log.retention.days') === null) {
            return null;
        }

        return ConfigFacade::integer('audit-log.retention.days');
    }
}
