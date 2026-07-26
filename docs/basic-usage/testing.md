---
title: "Testing Audit Logs"
description: "Fake the manager and assert application audit behavior."
weight: 4
---

## Fake the audit manager

Call `AuditLog::fake()` to replace the database manager with an in-memory fake:

```php
use HosmelQ\AuditLog\Facades\AuditLog;

use function HosmelQ\AuditLog\audit_log;

AuditLog::fake();

audit_log('document.published')
    ->tenant('org_123')
    ->record();

AuditLog::assertRecorded('document.published');
```

The fake preserves the manager's batching and correlation behavior without writing to the database.

## Assert recorded events

Assertions accept event strings or backed enums:

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

`assertRecorded()` and `assertNotRecorded()` also accept closures when an assertion depends on the complete payload.

## Inspect recorded data

Pass a closure to inspect matching `AuditLogData` objects:

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

## Assert correlations

Use `assertRecordedInCorrelation()` when several events must share one correlation ID:

```php
AuditLog::assertRecordedInCorrelation(
    'document.published',
    'notification.sent',
);
```

The assertion passes only when one correlation contains every expected event.
