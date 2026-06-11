<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Facades;

use BackedEnum;
use Closure;
use HosmelQ\AuditLog\AuditLogFake;
use HosmelQ\AuditLog\Contracts\AuditLogManager;
use HosmelQ\AuditLog\Data\AuditLogData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void assertNothingRecorded()
 * @method static void assertNotRecorded(BackedEnum|Closure|string $callback)
 * @method static void assertRecorded(BackedEnum|Closure|string $callback)
 * @method static void assertRecordedInCorrelation(BackedEnum|string ...$events)
 * @method static void assertRecordedTimes(BackedEnum|Closure|string $callback, int $times = 1)
 * @method static mixed correlate(Closure $callback, null|string $id = null)
 * @method static void record(AuditLogData|iterable<AuditLogData> $logs)
 * @method static Collection<int, AuditLogData> recorded(BackedEnum|Closure|string|null $callback = null)
 */
final class AuditLog extends Facade
{
    public static function fake(): AuditLogFake
    {
        return tap(new AuditLogFake(), function (AuditLogFake $fake): void {
            self::swap($fake);
        });
    }

    protected static function getFacadeAccessor(): string
    {
        return AuditLogManager::class;
    }
}
