---
title: "Batching and Correlating Logs"
description: "Record several logs in one call and link related logs with a correlation ID."
weight: 3
---

A correlation ID links logs that belong to the same operation. Logs get one in three ways: by setting it with `correlationId()`, by being recorded in the same batch, or by being recorded inside a correlation scope.

## Record a batch

Pass an array or other iterable of `AuditLogData` objects to the manager's `record()` method:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log()->record([
    audit_log('document.published')->tenant('org_123')->toAuditLogData(),
    audit_log('notification.sent')->tenant('org_123')->toAuditLogData(),
]);
```

All logs in one `record()` call are written in a single database transaction. If any insert fails, none of the logs are stored. Large batches are split into insert statements according to `audit-log.storage.insert_chunk_size`.

When a batch contains two or more logs, every log without a correlation ID receives the same generated ID. Logs that already have a correlation ID keep it. A batch with a single log does not receive a correlation ID. An empty or whitespace-only ID set with `correlationId()` counts as no correlation ID.

## Correlate separate calls

Use `correlate()` when logs recorded by separate calls should share one correlation ID:

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

Every log recorded inside the callback without its own correlation ID receives the scope's ID, including single logs and batches. The scope generates a new ID unless you pass one as the second argument:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log()->correlate(function (): void {
    audit_log('document.published')->record();
    audit_log('notification.sent')->record();
}, request()->header('X-Request-Id'));
```

`correlate()` returns the callback's return value. The `AuditLog` facade provides the same method.

## Nested scopes

Scopes can be nested. Logs use the innermost scope's ID, and the outer ID applies again after the inner callback finishes. A scope is also closed when its callback throws.

Passing an empty or whitespace-only ID does not open a new scope. The callback still runs, and logs use the outer scope's ID if there is one.

Correlation scopes belong to the current request or job.
