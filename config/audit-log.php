<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Attributes
    |--------------------------------------------------------------------------
    |
    | These options define the default bucket and source that will be used
    | when an audit log is recorded without explicit values. You may
    | override both values when recording application-specific events.
    |
    */

    'defaults' => [
        'bucket' => env('AUDIT_LOG_DEFAULTS_BUCKET', 'application'),
        'source' => env('AUDIT_LOG_DEFAULTS_SOURCE', 'platform'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Metadata
    |--------------------------------------------------------------------------
    |
    | These options determine whether the current HTTP request should fill
    | missing remote IP and user agent values. Console capture is disabled
    | by default because Laravel binds a synthetic request for Artisan.
    |
    */

    'request' => [
        'capture_in_console' => env('AUDIT_LOG_REQUEST_CAPTURE_IN_CONSOLE', false),
        'capture_remote_ip' => env('AUDIT_LOG_REQUEST_CAPTURE_REMOTE_IP', true),
        'capture_user_agent' => env('AUDIT_LOG_REQUEST_CAPTURE_USER_AGENT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log Retention
    |--------------------------------------------------------------------------
    |
    | This option controls how long audit logs should be retained. Setting the
    | value to null will keep audit logs indefinitely. When a value is set,
    | each inserted row receives an expiration date for model pruning.
    |
    */

    'retention' => [
        'days' => env('AUDIT_LOG_RETENTION_DAYS') === null ? null : (int) env('AUDIT_LOG_RETENTION_DAYS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log Storage
    |--------------------------------------------------------------------------
    |
    | Here you may configure the database connection and table that should be
    | used to store audit logs. A null connection will use the application's
    | default database connection. Inserts are chunked within a transaction.
    |
    */

    'storage' => [
        'connection' => env('AUDIT_LOG_STORAGE_CONNECTION'),
        'insert_chunk_size' => (int) env('AUDIT_LOG_STORAGE_INSERT_CHUNK_SIZE', 500),
        'table' => env('AUDIT_LOG_STORAGE_TABLE', 'audit_logs'),
    ],

];
