<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

final readonly class RequestMetadata
{
    public function __construct(private Application $app)
    {
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
