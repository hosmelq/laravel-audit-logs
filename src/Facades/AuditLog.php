<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Facades;

use Closure;
use HosmelQ\AuditLog\AuditLogFake;
use HosmelQ\AuditLog\Contracts\AuditLogManager;
use HosmelQ\AuditLog\Data\AuditLogData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void record(AuditLogData $log)
 * @method static void recordMany(iterable<AuditLogData> $logs)
 * @method static int commit()
 * @method static void assertRecorded(Closure|string $callback)
 * @method static void assertRecordedTimes(Closure|string $callback, int $times = 1)
 * @method static void assertNotRecorded(Closure|string $callback)
 * @method static void assertNothingRecorded()
 * @method static Collection<int, AuditLogData> recorded(Closure|string|null $callback = null)
 */
class AuditLog extends Facade
{
    public static function fake(): AuditLogFake
    {
        return tap(new AuditLogFake(), function (AuditLogFake $fake): void {
            static::swap($fake);
        });
    }

    protected static function getFacadeAccessor(): string
    {
        return AuditLogManager::class;
    }
}
