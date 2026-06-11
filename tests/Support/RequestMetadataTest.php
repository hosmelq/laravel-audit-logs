<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Support\RequestMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

it('does not capture request metadata in console by default', function (): void {
    app()->instance('request', Request::create('/', 'GET', server: [
        'HTTP_USER_AGENT' => 'Browser',
        'REMOTE_ADDR' => '203.0.113.10',
    ]));

    expect(resolve(RequestMetadata::class))
        ->remoteIp()->toBeNull()
        ->userAgent()->toBeNull();
});

it('does not capture request metadata when disabled', function (): void {
    Config::set('audit-log.request.capture_in_console', true);
    Config::set('audit-log.request.capture_remote_ip', false);
    Config::set('audit-log.request.capture_user_agent', false);

    app()->instance('request', Request::create('/', 'GET', server: [
        'HTTP_USER_AGENT' => 'Browser',
        'REMOTE_ADDR' => '203.0.113.10',
    ]));

    expect(resolve(RequestMetadata::class))
        ->remoteIp()->toBeNull()
        ->userAgent()->toBeNull();
});

it('captures missing request metadata', function (): void {
    Config::set('audit-log.request.capture_in_console', true);

    app()->instance('request', Request::create('/', 'GET', server: [
        'HTTP_USER_AGENT' => 'Browser',
        'REMOTE_ADDR' => '203.0.113.10',
    ]));

    expect(resolve(RequestMetadata::class))
        ->remoteIp()->toBe('203.0.113.10')
        ->userAgent()->toBe('Browser');
});
