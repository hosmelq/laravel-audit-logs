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
        return self::requestCaptureOption('capture_in_console', false);
    }

    public static function requestCaptureRemoteIp(): bool
    {
        return self::requestCaptureOption('capture_remote_ip', true);
    }

    public static function requestCaptureUserAgent(): bool
    {
        return self::requestCaptureOption('capture_user_agent', true);
    }

    public static function retentionDays(): null|int
    {
        /** @var null|int $days */
        $days = ConfigFacade::get('audit-log.retention.days');

        if ($days !== null && $days < 0) {
            throw new InvalidArgumentException(
                sprintf('Invalid audit-log.retention.days value [%d].', $days),
            );
        }

        return $days;
    }

    public static function storageConnection(): null|string
    {
        $connection = ConfigFacade::get('audit-log.storage.connection');

        if ($connection !== null && ! is_string($connection)) {
            throw new InvalidArgumentException(
                'Invalid audit-log.storage.connection value. Expected a connection name or null.',
            );
        }

        return $connection;
    }

    /**
     * @return positive-int
     */
    public static function storageInsertChunkSize(): int
    {
        $chunkSize = ConfigFacade::integer('audit-log.storage.insert_chunk_size', 500);

        if ($chunkSize < 1) {
            throw new InvalidArgumentException(
                'Invalid audit-log.storage.insert_chunk_size value.',
            );
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
                throw new InvalidArgumentException(
                    sprintf('Invalid audit-log.redaction.%s value. Expected string keys.', $option),
                );
            }
        }

        return array_values($keys);
    }

    private static function requestCaptureOption(string $option, bool $default): bool
    {
        $key = 'audit-log.request.'.$option;
        $value = ConfigFacade::get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value) && mb_trim($value) !== '') {
            $enabled = filter_var(mb_trim($value), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if ($enabled !== null) {
                return $enabled;
            }
        }

        throw new InvalidArgumentException(sprintf('Invalid %s value. Expected a boolean.', $key));
    }
}
