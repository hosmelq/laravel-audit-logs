---
title: "Batching and Correlating Logs"
description: "Record multiple events together or share a correlation identifier across calls."
weight: 1
---

## Record a batch

Pass an array or iterable of prepared logs to one `record()` call:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log()->record([
    audit_log('document.published')->tenant('org_123')->toAuditLogData(),
    audit_log('notification.sent')->tenant('org_123')->toAuditLogData(),
]);
```

The batch is written inside one database transaction. Large batches are split according to the configured insert chunk size.

## Understand batch correlation

When a batch contains more than one log, every log without an explicit `correlationId` receives the same generated correlation ID. Existing correlation IDs are preserved.

A single log does not receive a correlation ID automatically, even when passed inside an array or iterable.

## Correlate separate calls

Use `correlate()` when separate `record()` calls should share one correlation ID:

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

Pass an existing correlation ID as the second argument when the scope should reuse it:

```php
audit_log()->correlate(function (): void {
    audit_log('document.published')->record();
    audit_log('notification.sent')->record();
}, 'request_123');
```

Nested scopes restore the parent correlation after the inner callback finishes. The scope is also cleaned up when its callback throws.
