<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Pending Audit Log Repository
    |--------------------------------------------------------------------------
    |
    | The array driver batches audit logs for the current PHP lifecycle and
    | commits them when the application terminates. The redis driver stores
    | pending logs in a Redis list so multiple workers can drain them later.
    |
    */

    'driver' => env('AUDIT_LOG_DRIVER', 'array'),

    /*
    |--------------------------------------------------------------------------
    | Queue Audit Log Commits
    |--------------------------------------------------------------------------
    |
    | When enabled, commit will dispatch the captured audit log batch to the
    | queue instead of writing it to MySQL synchronously. This keeps request
    | shutdown fast and lets Laravel retry transient database failures.
    |
    */

    'queue' => [
        'commit_after_failure' => filter_var(env('AUDIT_LOG_QUEUE_COMMIT_AFTER_FAILURE', false), FILTER_VALIDATE_BOOL),
        'connection' => env('AUDIT_LOG_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'enabled' => filter_var(env('AUDIT_LOG_QUEUE_ENABLED', false), FILTER_VALIDATE_BOOL),
        'name' => env('AUDIT_LOG_QUEUE_NAME'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Commit
    |--------------------------------------------------------------------------
    |
    | When enabled, pending audit logs are committed in bulk at termination.
    | Disable this when you prefer scheduling the audit-log:commit command.
    |
    */

    'auto_commit' => filter_var(env('AUDIT_LOG_AUTO_COMMIT', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'bucket' => env('AUDIT_LOG_DEFAULTS_BUCKET', 'audit_logs'),
        'source' => env('AUDIT_LOG_DEFAULTS_SOURCE', 'platform'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Set days to null to keep audit logs indefinitely. When configured, each
    | inserted row receives an expires_at date derived from occurred_at.
    |
    */

    'retention' => [
        'days' => env('AUDIT_LOG_RETENTION_DAYS') === null ? null : (int) env('AUDIT_LOG_RETENTION_DAYS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    'database' => [
        'insert_chunk_size' => (int) env('AUDIT_LOG_DATABASE_INSERT_CHUNK_SIZE', 500),
        'logs_table' => env('AUDIT_LOG_DATABASE_LOGS_TABLE', 'audit_logs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis
    |--------------------------------------------------------------------------
    */

    'redis' => [
        'connection' => env('AUDIT_LOG_REDIS_CONNECTION'),
        'key' => env('AUDIT_LOG_REDIS_KEY', 'audit-log:pending'),
        'ttl' => (int) env('AUDIT_LOG_REDIS_TTL', 86400),
    ],

];
