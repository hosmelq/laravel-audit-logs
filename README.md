# Laravel Audit Logs

Laravel Audit Logs provides a simple, fluent API for recording audit events in Laravel
applications. Audit logs are buffered during the current lifecycle and committed in bulk to a
database table, with optional Redis buffering and queued database writes.

## Table of Contents

- [Introduction](#introduction)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Repository Driver](#repository-driver)
  - [Queue Commits](#queue-commits)
  - [Defaults](#defaults)
  - [Retention](#retention)
  - [Database](#database)
  - [Redis](#redis)
  - [Environment Variables](#environment-variables)
- [Recording Audit Logs](#recording-audit-logs)
  - [Using the Helper](#using-the-helper)
  - [Backed Enums](#backed-enums)
  - [Actors and Targets](#actors-and-targets)
  - [Event Properties](#event-properties)
  - [Explicit IDs and Occurrence Time](#explicit-ids-and-occurrence-time)
  - [Recording Data Objects](#recording-data-objects)
- [Committing Audit Logs](#committing-audit-logs)
  - [Automatic Commits](#automatic-commits)
  - [Manual Commits](#manual-commits)
  - [Scheduled Redis Commits](#scheduled-redis-commits)
- [Repository Drivers](#repository-drivers)
  - [Array Driver](#array-driver)
  - [Redis Driver](#redis-driver)
- [Queued Database Writes](#queued-database-writes)
- [Custom Audit Log Model](#custom-audit-log-model)
- [Pruning Expired Audit Logs](#pruning-expired-audit-logs)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [License](#license)

## Introduction

Audit logs are durable records of actions that happened in your application, such as publishing a
document, updating account settings, or inviting a team member. This package provides a clean API
to collect audit logs and write them in batches with actor, target, request, and event context.

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

Finally, run your migrations:

```bash
php artisan migrate
```

## Configuration

After publishing the package assets, the configuration file will be located at
`config/audit-log.php`. This configuration file allows you to configure buffering, queued writes,
defaults, retention, database storage, and Redis storage.

### Repository Driver

You may specify the repository driver using the `audit-log.driver` configuration value or the
`AUDIT_LOG_DRIVER` environment variable. Supported drivers are `array` and `redis`.

### Queue Commits

Queue commits allow the package to dispatch captured batches to Laravel's queue instead of writing
to the database during the current request or command lifecycle.

### Defaults

You may configure the default bucket and source used by the fluent builder. These values are
applied when the builder does not provide an explicit bucket or source.

### Retention

You may configure a retention period in days. When retention is configured, each inserted row
receives an `expires_at` date derived from `occurred_at`.

### Database

The package writes audit logs through the configured audit log model. You may configure the table
name and insert chunk size through the `audit-log.database` configuration values.

### Redis

The Redis repository stores pending audit logs in a Redis list. You may configure the Redis
connection, pending key, and TTL through the `audit-log.redis` configuration values.

### Environment Variables

The following environment variables are supported:

- `AUDIT_LOG_AUTO_COMMIT` (default: `true`)
- `AUDIT_LOG_DATABASE_INSERT_CHUNK_SIZE` (default: `500`)
- `AUDIT_LOG_DATABASE_LOGS_TABLE` (default: `audit_logs`)
- `AUDIT_LOG_DEFAULTS_BUCKET` (default: `audit_logs`)
- `AUDIT_LOG_DEFAULTS_SOURCE` (default: `platform`)
- `AUDIT_LOG_DRIVER` (default: `array`)
- `AUDIT_LOG_QUEUE_COMMIT_AFTER_FAILURE` (default: `false`)
- `AUDIT_LOG_QUEUE_CONNECTION` (default: `QUEUE_CONNECTION` or `sync`)
- `AUDIT_LOG_QUEUE_ENABLED` (default: `false`)
- `AUDIT_LOG_QUEUE_NAME` (default: `null`)
- `AUDIT_LOG_REDIS_CONNECTION` (default: `null`)
- `AUDIT_LOG_REDIS_KEY` (default: `audit-log:pending`)
- `AUDIT_LOG_REDIS_TTL` (default: `86400`)
- `AUDIT_LOG_RETENTION_DAYS` (default: `null`)

## Recording Audit Logs

### Using the Helper

Use the `audit_log` helper to start a pending audit log:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.publish')
    ->tenant('org_123')
    ->description('Published document')
    ->actor(type: 'user', id: 'member_123', name: 'Hosmel Quintana')
    ->target(type: 'document', id: 'doc_123', name: 'Quarterly report')
    ->record();
```

Calling `record` stores the audit log in the configured repository. The package assigns a ULID
when no ID is provided.

### Backed Enums

The fluent builder and data objects accept PHP backed enums for audit values that usually come
from your domain: events, actor types, target types, buckets, and sources. Enum values are
normalized to strings before buffering, queueing, or writing to the database.

```php
use function HosmelQ\AuditLog\audit_log;

enum AuditEvent: string
{
    case DocumentPublished = 'document.publish';
}

enum AuditActorType: string
{
    case User = 'user';
}

enum AuditTargetType: string
{
    case Document = 'document';
}

audit_log(AuditEvent::DocumentPublished)
    ->tenant('org_123')
    ->actor(type: AuditActorType::User, id: 'member_123')
    ->target(type: AuditTargetType::Document, id: 'doc_123')
    ->record();
```

IDs, names, descriptions, IP addresses, user agents, and custom properties remain regular scalar
values.

### Actors and Targets

Actors and targets are stored with their type, ID, optional name, and properties:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.publish')
    ->tenant('org_123')
    ->actor(
        type: 'user',
        id: 'member_123',
        name: 'Hosmel Quintana',
        properties: ['team' => 'development'],
    )
    ->target(
        type: 'document',
        id: 'doc_123',
        name: 'Quarterly report',
        properties: ['folder' => 'reports'],
    )
    ->record();
```

If no actor is provided, the builder uses a system actor.

### Event Properties

Use `properties`, `remoteIp`, and `userAgent` to attach event and request context:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.publish')
    ->tenant('org_123')
    ->properties(['request_id' => 'req_123'])
    ->remoteIp(request()->ip())
    ->userAgent(request()->userAgent())
    ->record();
```

### Explicit IDs and Occurrence Time

You may provide your own audit log ID, batch ID, source, bucket, and occurrence time:

```php
use Carbon\CarbonImmutable;

use function HosmelQ\AuditLog\audit_log;

audit_log('document.publish')
    ->tenant('org_123')
    ->id('01J00000000000000000000011')
    ->batchId('batch_123')
    ->bucket('audit_logs')
    ->source('platform')
    ->occurredAt(CarbonImmutable::now())
    ->record();
```

### Recording Data Objects

You may also record `AuditLogData` objects directly through the facade:

```php
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;

AuditLog::record(new AuditLogData(
    actor: new AuditLogActorData(type: 'user', id: 'member_123'),
    event: 'document.publish',
    tenantId: 'org_123',
));
```

The same backed enum support shown above is available when constructing `AuditLogData`,
`AuditLogActorData`, and `AuditLogTargetData` directly.

When recording data objects directly, pass `bucket` and `source` explicitly if you need values
other than the `AuditLogData` constructor defaults.

To record multiple logs as one logical batch, use `recordMany`:

```php
AuditLog::recordMany([
    new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        event: 'document.publish',
        tenantId: 'org_123',
    ),
    new AuditLogData(
        actor: new AuditLogActorData(type: 'user', id: 'member_123'),
        event: 'document.archive',
        tenantId: 'org_123',
    ),
]);
```

## Committing Audit Logs

### Automatic Commits

By default, pending audit logs are committed when the application terminates and after a queued
job is processed successfully. When a queued job throws an exception, pending audit logs are
discarded so a failed attempt does not get committed by the next job.

Set `AUDIT_LOG_QUEUE_COMMIT_AFTER_FAILURE=true` when you want to audit failed queued attempts.

### Manual Commits

Commit pending audit logs manually through the helper or facade:

```php
use HosmelQ\AuditLog\Facades\AuditLog;

AuditLog::commit();
```

```php
use function HosmelQ\AuditLog\audit_log;

audit_log()->commit();
```

You may also commit through Artisan:

```bash
php artisan audit-log:commit
```

With the default `array` driver, the command only commits audit logs buffered in the current
Artisan process. Use the `redis` driver when you need a scheduled command to drain audit logs
buffered by other processes.

### Scheduled Redis Commits

When using Redis for distributed buffering, disable automatic commits and schedule the commit
command:

```env
AUDIT_LOG_AUTO_COMMIT=false
AUDIT_LOG_DRIVER=redis
```

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('audit-log:commit')->everyMinute();
```

## Repository Drivers

You may select a repository driver globally via configuration.

### Array Driver

The `array` driver stores pending audit logs in memory for the current PHP lifecycle. It is the
default driver and works well when the same process records and commits the logs.

### Redis Driver

The `redis` driver stores pending audit logs in Redis so multiple processes can add logs to the
same pending list. During commit, the repository drains the pending list into a processing list
before the database write. After a successful commit, the processing list is flushed.

## Queued Database Writes

Repository drivers control where pending logs are buffered. Queue commits control how database
writes happen after a batch has been captured.

Enable queue commits when you want Laravel's queue workers to perform the database write:

```env
AUDIT_LOG_QUEUE_CONNECTION=redis
AUDIT_LOG_QUEUE_ENABLED=true
AUDIT_LOG_QUEUE_NAME=audit-logs
```

The dispatched job receives the captured `AuditLogData` batch and writes it through the package
writer. When queue commits are enabled, `audit-log:commit` reports the batch as queued for commit.

## Custom Audit Log Model

Your custom model must extend the package model. Use this when you need to customize the model
connection or ID generation behavior:

```php
namespace App\Models;

use HosmelQ\AuditLog\Models\AuditLog as BaseAuditLog;

class AuditLog extends BaseAuditLog
{
    protected $connection = 'audit';
}
```

Register the model from one of your service providers:

```php
use App\Models\AuditLog;
use HosmelQ\AuditLog\DatabaseAuditLogManager;

DatabaseAuditLogManager::useModel(AuditLog::class);
```

Audit logs are written with bulk inserts. Custom model connections and ULID generation are used,
but Eloquent events, mutators, and casts are not applied during writes.

## Pruning Expired Audit Logs

When retention is configured, expired audit logs may be deleted with Laravel's `model:prune`
command. Schedule the package model, or your custom model if you registered one:

```php
use HosmelQ\AuditLog\Models\AuditLog;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', [
    '--model' => [AuditLog::class],
])->daily();
```

## Testing

Use `AuditLog::fake` to replace the audit log manager with an in-memory fake during a test:

```php
use HosmelQ\AuditLog\Facades\AuditLog;

use function HosmelQ\AuditLog\audit_log;

AuditLog::fake();

audit_log('document.publish')
    ->tenant('org_123')
    ->actor(type: 'user', id: 'member_123')
    ->record();

AuditLog::assertRecorded('document.publish');
AuditLog::assertNotRecorded('document.archive');
AuditLog::assertRecordedTimes('document.publish', 1);
```

Pass a closure when the test needs to verify the recorded audit log payload. The assertion passes
when at least one recorded audit log makes the closure return `true`:

```php
use HosmelQ\AuditLog\Data\AuditLogData;

AuditLog::assertRecorded(function (AuditLogData $log): bool {
    return $log->event === 'document.publish'
        && $log->tenantId === 'org_123'
        && $log->actor->type === 'user'
        && $log->actor->id === 'member_123';
});
```

Use `recorded` to retrieve the captured audit logs as a Laravel collection of `AuditLogData`
instances. Pass an event name or closure to filter the collection:

```php
$log = AuditLog::recorded('document.publish')->first();

expect($log)
    ->event->toBe('document.publish')
    ->tenantId->toBe('org_123')
    ->and($log->actor)
    ->type->toBe('user')
    ->id->toBe('member_123');
```

To run this package's test suite:

```bash
composer test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## Contributing

Pull requests are welcome. Please run the test suite before submitting changes.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
