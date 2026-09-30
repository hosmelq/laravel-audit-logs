---
title: "Configuration"
description: "Configure defaults, request metadata, retention, and storage."
weight: 3
---

The published `config/audit-log.php` file controls default attributes, request metadata, retention, and database storage.

## Default attributes

`audit-log.defaults.bucket` and `audit-log.defaults.source` fill missing `bucket` and `source` values when a log is prepared. Explicit values always take precedence.

## Request metadata

The package can fill missing `remoteIp` and `userAgent` values from the current request. Remote IP and user agent capture are enabled by default.

Console capture is disabled because Laravel binds a synthetic request while running Artisan. Enable `audit-log.request.capture_in_console` only when that request should be used.

Values set explicitly through `remoteIp()` or `userAgent()` are preserved.

Call `withoutRequestMetadata()` on an individual event to omit both values, including explicit ones. See [redacting sensitive data](advanced-usage/redacting-sensitive-data).

## Sensitive metadata

`audit-log.redaction.exclude` lists metadata keys to remove. `audit-log.redaction.mask` lists keys whose values should be replaced by `audit-log.redaction.replacement`.

Rules apply to event, actor, and target metadata. Both lists are empty by default. Exclusion takes precedence over masking.

## Storage

`audit-log.storage.connection` and `audit-log.storage.table` select where logs are stored. A `null` connection uses Laravel's default database connection.

Batch inserts are split by `audit-log.storage.insert_chunk_size`. All chunks from one `record()` call are written inside the same database transaction. The chunk size must be greater than zero.

## Retention and pruning

Set `audit-log.retention.days` to a non-negative number of days or `null`. A `null` value keeps new audit logs indefinitely.

`audit-log.retention.events` and `audit-log.retention.buckets` provide periods for exact event names and buckets. Explicit `retentionDays()` values take precedence over event rules, followed by bucket rules and the global default. See [configuring retention](advanced-usage/configuring-retention).

When a log is written, its `expires_at` value is calculated from `occurred_at` using the current retention setting. Changing the setting does not update rows that already exist.

The package model uses Laravel's `MassPrunable` trait. Schedule `model:prune` to delete rows whose `expires_at` value has passed:

```php
use HosmelQ\AuditLog\Models\AuditLog;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', [
    '--model' => [AuditLog::class],
])->daily();
```

Only rows with a non-null, expired `expires_at` value are pruned.

## Environment variables

| Configuration key | Environment variable | Default |
| --- | --- | --- |
| `defaults.bucket` | `AUDIT_LOG_DEFAULTS_BUCKET` | `application` |
| `defaults.source` | `AUDIT_LOG_DEFAULTS_SOURCE` | `platform` |
| `request.capture_in_console` | `AUDIT_LOG_REQUEST_CAPTURE_IN_CONSOLE` | `false` |
| `request.capture_remote_ip` | `AUDIT_LOG_REQUEST_CAPTURE_REMOTE_IP` | `true` |
| `request.capture_user_agent` | `AUDIT_LOG_REQUEST_CAPTURE_USER_AGENT` | `true` |
| `retention.days` | `AUDIT_LOG_RETENTION_DAYS` | `null` |
| `storage.connection` | `AUDIT_LOG_STORAGE_CONNECTION` | `null` |
| `storage.insert_chunk_size` | `AUDIT_LOG_STORAGE_INSERT_CHUNK_SIZE` | `500` |
| `storage.table` | `AUDIT_LOG_STORAGE_TABLE` | `audit_logs` |
