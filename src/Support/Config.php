<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Support;

use Illuminate\Support\Facades\Config as ConfigFacade;
use InvalidArgumentException;

final class Config
{
    public static function defaultsBucket(): string
    {
        return ConfigFacade::string('audit-log.defaults.bucket');
    }

    public static function defaultsSource(): string
    {
        return ConfigFacade::string('audit-log.defaults.source');
    }

    public static function requestCaptureInConsole(): bool
    {
        return ConfigFacade::boolean('audit-log.request.capture_in_console', false);
    }

    public static function requestCaptureRemoteIp(): bool
    {
        return ConfigFacade::boolean('audit-log.request.capture_remote_ip', true);
    }

    public static function requestCaptureUserAgent(): bool
    {
        return ConfigFacade::boolean('audit-log.request.capture_user_agent', true);
    }

    public static function retentionDays(): null|int
    {
        /** @var null|int $days */
        $days = ConfigFacade::get('audit-log.retention.days');

        if ($days !== null && $days < 0) {
            throw new InvalidArgumentException(sprintf('Invalid audit-log.retention.days value [%d].', $days));
        }

        return $days;
    }

    public static function storageConnection(): null|string
    {
        /** @var null|string $connection */
        $connection = ConfigFacade::get('audit-log.storage.connection');

        return $connection;
    }

    /**
     * @return positive-int
     */
    public static function storageInsertChunkSize(): int
    {
        $chunkSize = ConfigFacade::integer('audit-log.storage.insert_chunk_size', 500);

        if ($chunkSize < 1) {
            throw new InvalidArgumentException('Invalid audit-log.storage.insert_chunk_size value.');
        }

        return $chunkSize;
    }

    public static function storageTable(): string
    {
        return ConfigFacade::string('audit-log.storage.table');
    }
}
