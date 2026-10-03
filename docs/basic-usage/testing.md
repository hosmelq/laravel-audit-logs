---
title: "Testing Audit Logs"
description: "Replace the database with an in-memory fake and assert recorded logs."
weight: 5
---

## Fake the audit log manager

Call `AuditLog::fake()` to keep recorded logs in memory instead of writing them to the database:

```php
use HosmelQ\AuditLog\Facades\AuditLog;

use function HosmelQ\AuditLog\audit_log;

AuditLog::fake();

audit_log('document.published')
    ->tenant('org_123')
    ->record();

AuditLog::assertRecorded('document.published');
```

The fake replaces the audit log manager for the `audit_log` helper and the facade. It applies the same batch correlation, correlation scopes, and [redaction rules](../advanced-usage/redacting-sensitive-data) as the database manager.

## Assert recorded logs

Each assertion accepts an event name or a backed enum:

```php
AuditLog::assertRecorded('document.published');
AuditLog::assertNotRecorded('document.archived');
AuditLog::assertRecordedTimes('document.published', 2);
AuditLog::assertNothingRecorded();
```

`assertRecordedTimes()` expects exactly one log when you omit the count.

## Inspect recorded data

`assertRecorded()`, `assertNotRecorded()`, and `assertRecordedTimes()` also accept a closure. The closure receives each recorded `AuditLogData` object and returns `true` for matching logs:

```php
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;

AuditLog::assertRecorded(fn (AuditLogData $log): bool => $log->event === 'document.updated'
    && $log->tenantId === 'org_123'
    && $log->actor->id === 'member_123'
    && $log->changes?->toArray() === [
        'before' => ['published' => false],
        'after' => ['published' => true],
    ]);
```

The data object exposes the same values as the stored row: `actor` and `targets` hold `AuditLogActorData` and `AuditLogTargetData` objects, and `changes` holds an `AuditLogChangesData` object or `null` (stored in the `attribute_changes` column). Call `toArray()` on any of them for an array.

Use `recorded()` to retrieve logs as a collection, optionally filtered by event name, backed enum, or closure:

```php
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;

$all = AuditLog::recorded();
$published = AuditLog::recorded('document.published');
$tenantLogs = AuditLog::recorded(fn (AuditLogData $log): bool => $log->tenantId === 'org_123');
```

## Assert correlations

Use `assertRecordedInCorrelation()` when several events must share one correlation ID:

```php
AuditLog::assertRecordedInCorrelation('document.published', 'notification.sent');
```

The assertion passes when at least one correlation ID is shared by logs for every listed event. Logs without a correlation ID are ignored. See [correlating logs](../advanced-usage/correlating-logs).

## Differences from the database

- Recorded logs reflect the redaction rules configured when `record()` is called. The `AuditLogData` objects you pass in are not changed.
- Request metadata is read when the builder prepares a log. Tests run in the console, so the builder captures it only when `audit-log.request.capture_in_console` is enabled. Like database storage, the fake fills a missing remote IP and user agent on `AuditLogData` objects you create yourself, unless `captureRequestMetadata` is `false`.
- Retention and storage options are not used.

## Reset context resolvers

[Context resolvers](../advanced-usage/configuring-context) are stored statically and are not reset between tests. Clear any resolver a test registers:

```php
use HosmelQ\AuditLog\AuditLogContext;

AuditLogContext::resolveActorUsing(null);
AuditLogContext::resolveTenantUsing(null);
```
