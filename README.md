# Laravel Audit Logs

Laravel Audit Logs provides a simple, fluent API for recording application audit events. You may
include actors, targets, request metadata, and custom metadata for each event.

## Table of Contents

- [Introduction](#introduction)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Default Attributes](#default-attributes)
  - [Request Metadata](#request-metadata)
  - [Storage Configuration](#storage-configuration)
  - [Environment Variables](#environment-variables)
- [Recording Audit Logs](#recording-audit-logs)
  - [The Audit Log Helper](#the-audit-log-helper)
  - [Fluent Attributes](#fluent-attributes)
  - [Audit Log Identities](#audit-log-identities)
  - [Recording Multiple Logs](#recording-multiple-logs)
  - [Correlating Logs](#correlating-logs)
  - [Defaults & Missing Values](#defaults--missing-values)
- [Enum Values](#enum-values)
- [Reading Audit Logs](#reading-audit-logs)
- [Retention & Pruning](#retention--pruning)
- [Adding a Custom Audit Log Model](#adding-a-custom-audit-log-model)
- [Testing Your Application](#testing-your-application)
  - [Faking Audit Logs](#faking-audit-logs)
  - [Assertions](#assertions)
  - [Inspecting Recorded Logs](#inspecting-recorded-logs)
- [Running the Test Suite](#running-the-test-suite)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [License](#license)

## Introduction

Audit logs capture meaningful application actions, such as publishing a document, changing account
settings, or performing an administrative action. This package provides a clean API to explicitly
record those events to a database table with sensible defaults.

## Installation

First, install the package via Composer:

```bash
composer require hosmelq/laravel-audit-logs
```

Next, publish the configuration and migration files:

```bash
php artisan audit-log:install
```

If you prefer, you may publish the assets manually:

```bash
php artisan vendor:publish --tag="audit-log-config"
php artisan vendor:publish --tag="audit-log-migrations"
```

The installer can run the migrations for you. If you skip that prompt, run them manually:

```bash
php artisan migrate
```

## Configuration

After publishing the package assets, the configuration file will be located at
`config/audit-log.php`. This configuration file allows you to specify default attributes, request
metadata capture, retention, and storage.

### Default Attributes

You may specify the default `bucket` and `source` values using the `audit-log.defaults.bucket` and
`audit-log.defaults.source` configuration values. These values are used when an audit log is
recorded without explicit values.

### Request Metadata

HTTP requests may fill missing `remoteIp` and `userAgent` values when request capture is enabled.
Capture is enabled for remote IP and user agent by default, but console capture is disabled unless
`audit-log.request.capture_in_console` is enabled.

### Storage Configuration

Audit logs are written to the configured database table. You may configure the database connection
and table name used to store audit logs.

A `null` storage connection uses Laravel's default database connection.

### Environment Variables

The following environment variables are supported:

- `AUDIT_LOG_DEFAULTS_BUCKET` (default: `application`)
- `AUDIT_LOG_DEFAULTS_SOURCE` (default: `platform`)
- `AUDIT_LOG_REQUEST_CAPTURE_IN_CONSOLE` (default: `false`)
- `AUDIT_LOG_REQUEST_CAPTURE_REMOTE_IP` (default: `true`)
- `AUDIT_LOG_REQUEST_CAPTURE_USER_AGENT` (default: `true`)
- `AUDIT_LOG_RETENTION_DAYS` (default: `null`)
- `AUDIT_LOG_STORAGE_CONNECTION` (default: `null`)
- `AUDIT_LOG_STORAGE_INSERT_CHUNK_SIZE` (default: `500`)
- `AUDIT_LOG_STORAGE_TABLE` (default: `audit_logs`)

## Recording Audit Logs

### The Audit Log Helper

Call the `audit_log` helper with an event, set the fields you care about, and call `record`:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.published')
    ->tenant('org_123')
    ->record();
```

Call `audit_log()` without an event to record prepared logs or run a correlation scope.

### Fluent Attributes

Use the fluent builder to attach common audit log attributes:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.published')
    ->tenant('org_123')
    ->actor(type: 'user', id: 'member_123')
    ->target(type: 'document', id: 'doc_123')
    ->metadata(['visibility' => 'public'])
    ->record();
```

Optional fields include `description`, `bucket`, `source`, `occurredAt`, `remoteIp`, `userAgent`,
`id`, and `correlationId`. Tenant, actor, and target IDs may be integers or strings.

You may call `toAuditLogData` when you need the data object without recording it immediately:

```php
use function HosmelQ\AuditLog\audit_log;

$log = audit_log('document.published')
    ->tenant('org_123')
    ->target(type: 'document', id: 'doc_123')
    ->toAuditLogData();

audit_log()->record($log);
```

You may also record existing `AuditLogData` instances through the facade:

```php
use HosmelQ\AuditLog\Facades\AuditLog;

AuditLog::record($log);
```

### Audit Log Identities

Implement `HasAuditLogIdentity` when an object should be usable as an audit log actor or target:

```php
use HosmelQ\AuditLog\Contracts\HasAuditLogIdentity;
use HosmelQ\AuditLog\Support\AuditLogIdentity;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements HasAuditLogIdentity
{
    public function auditLogIdentity(): AuditLogIdentity
    {
        return new AuditLogIdentity(
            id: $this->id,
            name: $this->email,
            type: $this->getMorphClass(),
        );
    }
}
```

Pass the object directly to `actor` or `target`:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('auth.sessions.delete')
    ->actor($user)
    ->target($organization)
    ->tenant($organization->id)
    ->record();
```

### Recording Multiple Logs

Pass an iterable to record multiple logs at once:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log()->record([
    audit_log('document.published')->tenant('org_123')->toAuditLogData(),
    audit_log('notification.sent')->tenant('org_123')->toAuditLogData(),
]);
```

When an iterable contains more than one log, logs without an explicit `correlationId` receive the
same generated correlation ID.

### Correlating Logs

Use `correlate` when separate `record` calls should share one correlation ID:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log()->correlate(function (): void {
    audit_log('document.published')
        ->target(type: 'document', id: 'doc_123')
        ->record();

    audit_log('notification.sent')
        ->target(type: 'document', id: 'doc_123')
        ->record();
});
```

Pass a correlation ID as the second argument when the scope should reuse an existing ID.

### Defaults & Missing Values

Missing values follow these rules:

- If no actor is set, a system actor is used.
- If no bucket or source is set, the configured default is used.
- If no ID is set, one is generated.
- If no occurrence time is set, the current time is used.
- If request metadata is not provided, request capture may fill it.

## Enum Values

The fluent helper accepts strings or backed enums for events, buckets, sources, actor types, and
target types. Backed enum values are stored as strings.

```php
use function HosmelQ\AuditLog\audit_log;

enum AuditEvent: string
{
    case DocumentPublished = 'document.published';
}

audit_log(AuditEvent::DocumentPublished)
    ->tenant('org_123')
    ->record();
```

## Reading Audit Logs

Use the package model to query stored logs:

```php
use HosmelQ\AuditLog\Models\AuditLog;

$logs = AuditLog::query()
    ->where('tenant_id', 'org_123')
    ->where('bucket', 'application')
    ->latest('occurred_at')
    ->get();
```

JSON columns such as `metadata`, `actor_metadata`, and `targets` are cast to arrays, while
`occurred_at`, `inserted_at`, and `expires_at` are cast to immutable dates.

## Retention & Pruning

Set `audit-log.retention.days` to expire audit logs after a fixed number of days. A `null` value
keeps audit logs indefinitely.

When retention is configured, each row receives an `expires_at` value derived from `occurred_at`.
Prune expired rows using Laravel's `model:prune` command:

```php
use HosmelQ\AuditLog\Models\AuditLog;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', [
    '--model' => [AuditLog::class],
])->daily();
```

## Adding a Custom Audit Log Model

Use a custom model when you need application-specific query scopes or casts. The model must extend
the package model:

```php
namespace App\Models;

use HosmelQ\AuditLog\Models\AuditLog as BaseAuditLog;

class AuditLog extends BaseAuditLog
{
}
```

Register the model once from a service provider:

```php
use App\Models\AuditLog;
use HosmelQ\AuditLog\DatabaseAuditLogManager;

DatabaseAuditLogManager::useModel(AuditLog::class);
```

## Testing Your Application

### Faking Audit Logs

Call `AuditLog::fake()` to replace the manager with an in-memory fake:

```php
use HosmelQ\AuditLog\Facades\AuditLog;

use function HosmelQ\AuditLog\audit_log;

AuditLog::fake();

audit_log('document.published')
    ->tenant('org_123')
    ->record();

AuditLog::assertRecorded('document.published');
```

### Assertions

Use fake assertions to verify events by string or backed enum:

```php
AuditLog::assertRecorded('document.published');
AuditLog::assertNotRecorded('document.archived');
AuditLog::assertRecordedInCorrelation('document.published', 'notification.sent');
AuditLog::assertRecordedTimes('document.published', 1);
```

Use `assertNothingRecorded` when no logs should have been recorded:

```php
AuditLog::assertNothingRecorded();
```

### Inspecting Recorded Logs

Pass a closure to inspect recorded `AuditLogData` objects:

```php
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;

AuditLog::assertRecorded(function (AuditLogData $log): bool {
    return $log->event === 'document.published'
        && $log->tenantId === 'org_123'
        && $log->actor->id === 'member_123';
});
```

Retrieve recorded logs as a collection and filter them by event string, backed enum, or closure:

```php
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;

$all = AuditLog::recorded();
$logs = AuditLog::recorded('document.published');
$filtered = AuditLog::recorded(fn (AuditLogData $log): bool => $log->tenantId === 'org_123');
```

## Running the Test Suite

```bash
composer test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## Contributing

Pull requests are welcome. Please run the test suite before submitting changes.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
