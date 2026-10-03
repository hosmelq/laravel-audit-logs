---
title: "Configuration"
description: "Configure default attributes, request metadata, redaction, retention, and storage."
weight: 3
---

The published `config/audit-log.php` file contains five groups of options. Most options can be set through environment variables; see the [table at the end of this page](#environment-variables).

## Default attributes

| Option | Default | Description |
| --- | --- | --- |
| `defaults.bucket` | `application` | Bucket used when a log does not set one. |
| `defaults.source` | `platform` | Source used when a log does not set one. |

The builder applies these defaults when it prepares a log. Values passed to `bucket()` and `source()` take precedence. `AuditLogData` objects you create yourself must set both values.

## Request metadata

| Option | Default | Description |
| --- | --- | --- |
| `request.capture_remote_ip` | `true` | Fill a missing remote IP from the current request. |
| `request.capture_user_agent` | `true` | Fill a missing user agent from the current request. |
| `request.capture_in_console` | `false` | Allow capture while the application runs in the console. |

Console capture is disabled because Laravel binds a synthetic request when running Artisan commands. Queue workers also run in the console, so logs recorded from queued jobs have no request metadata unless you set it yourself. Enable `capture_in_console` only when the console request holds the values you want to store.

See [request metadata](basic-usage/recording-audit-logs#request-metadata) for how captured and explicit values are combined.

## Redaction

| Option | Default | Description |
| --- | --- | --- |
| `redaction.exclude` | `[]` | Metadata keys removed from logs. |
| `redaction.mask` | `[]` | Metadata keys whose values are replaced. |
| `redaction.replacement` | `[REDACTED]` | Value stored for masked keys. |

Both lists must contain only strings; any other value throws an `InvalidArgumentException` when a log is recorded. See [redacting sensitive data](advanced-usage/redacting-sensitive-data) for how the rules are applied.

## Retention

| Option | Default | Description |
| --- | --- | --- |
| `retention.days` | `null` | Number of days to keep new logs, or `null` to keep them indefinitely. |

When a log is written, its `expires_at` value is set to `occurred_at` plus the configured number of days. A `null` value leaves `expires_at` empty, and `0` makes logs expire as soon as they occur. Changing the option does not update rows that already exist. A negative value throws an `InvalidArgumentException` when a log is written.

The package model uses Laravel's `MassPrunable` trait. Schedule the `model:prune` command to delete expired rows:

```php
use HosmelQ\AuditLog\Models\AuditLog;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', [
    '--model' => [AuditLog::class],
])->daily();
```

The command deletes rows whose `expires_at` value is not null and is in the past. Rows without an expiration date are never pruned. If you register a [custom model](advanced-usage/custom-audit-log-model), you may pass that class instead.

## Storage

| Option | Default | Description |
| --- | --- | --- |
| `storage.connection` | `null` | Database connection for audit logs. `null` uses the default connection. |
| `storage.table` | `audit_logs` | Table that stores audit logs. |
| `storage.insert_chunk_size` | `500` | Maximum number of rows per insert statement. |

The migrations, the model, and the writer all read the connection and table from this group, so set them before running the migrations.

Each `record()` call writes its logs inside one database transaction. Larger batches are split into insert statements of `insert_chunk_size` rows, and every chunk is rolled back if one fails. The chunk size must be at least `1`; a smaller value throws an `InvalidArgumentException` when a log is written.

## Environment variables

| Option | Environment variable |
| --- | --- |
| `defaults.bucket` | `AUDIT_LOG_DEFAULTS_BUCKET` |
| `defaults.source` | `AUDIT_LOG_DEFAULTS_SOURCE` |
| `request.capture_in_console` | `AUDIT_LOG_REQUEST_CAPTURE_IN_CONSOLE` |
| `request.capture_remote_ip` | `AUDIT_LOG_REQUEST_CAPTURE_REMOTE_IP` |
| `request.capture_user_agent` | `AUDIT_LOG_REQUEST_CAPTURE_USER_AGENT` |
| `retention.days` | `AUDIT_LOG_RETENTION_DAYS` |
| `storage.connection` | `AUDIT_LOG_STORAGE_CONNECTION` |
| `storage.insert_chunk_size` | `AUDIT_LOG_STORAGE_INSERT_CHUNK_SIZE` |
| `storage.table` | `AUDIT_LOG_STORAGE_TABLE` |

The redaction options have no environment variables. Edit the lists in `config/audit-log.php`.
