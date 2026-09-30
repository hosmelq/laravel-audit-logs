<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Support;

use HosmelQ\AuditLog\Data\AuditLogData;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

final readonly class RequestMetadata
{
    public function __construct(private Application $app)
    {
    }

    public function fill(AuditLogData $log): AuditLogData
    {
        if (! $log->captureRequestMetadata) {
            return $log;
        }

        return $log->withRequestMetadata(
            remoteIp: $log->remoteIp ?? $this->remoteIp(),
            userAgent: $log->userAgent ?? $this->userAgent(),
        );
    }

    public function remoteIp(): null|string
    {
        return Config::requestCaptureRemoteIp() ? $this->request()?->ip() : null;
    }

    public function userAgent(): null|string
    {
        return Config::requestCaptureUserAgent() ? $this->request()?->userAgent() : null;
    }

    private function request(): null|Request
    {
        if (! $this->app->bound('request')) {
            return null;
        }

        if ($this->app->runningInConsole() && ! Config::requestCaptureInConsole()) {
            return null;
        }

        return $this->app->make('request');
    }
}
