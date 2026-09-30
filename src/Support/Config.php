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

    /**
     * @return list<string>
     */
    public static function redactionExclude(): array
    {
        return self::redactionKeys('exclude');
    }

    /**
     * @return list<string>
     */
    public static function redactionMask(): array
    {
        return self::redactionKeys('mask');
    }

    public static function redactionReplacement(): string
    {
        return ConfigFacade::string('audit-log.redaction.replacement', '[REDACTED]');
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

    public static function retentionDays(null|string $event = null, null|string $bucket = null): null|int
    {
        $events = ConfigFacade::array('audit-log.retention.events', []);
        $buckets = ConfigFacade::array('audit-log.retention.buckets', []);
        $key = 'audit-log.retention.days';

        if ($event !== null && array_key_exists($event, $events)) {
            $days = $events[$event];
            $key = 'audit-log.retention.events.'.$event;
        } elseif ($bucket !== null && array_key_exists($bucket, $buckets)) {
            $days = $buckets[$bucket];
            $key = 'audit-log.retention.buckets.'.$bucket;
        } else {
            $days = ConfigFacade::get($key);
        }

        if ($days !== null && ! is_int($days)) {
            throw new InvalidArgumentException(sprintf('Invalid %s value. Expected a non-negative integer or null.', $key));
        }

        if ($days !== null && $days < 0) {
            throw new InvalidArgumentException(sprintf('Invalid %s value [%d].', $key, $days));
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

    /**
     * @return list<string>
     */
    private static function redactionKeys(string $option): array
    {
        $keys = ConfigFacade::array('audit-log.redaction.'.$option, []);

        foreach ($keys as $key) {
            if (! is_string($key)) {
                throw new InvalidArgumentException(sprintf('Invalid audit-log.redaction.%s value. Expected string keys.', $option));
            }
        }

        return array_values($keys);
    }
}
