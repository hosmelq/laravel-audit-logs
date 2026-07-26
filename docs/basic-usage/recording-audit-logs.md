---
title: "Recording Audit Logs"
description: "Record audit events through the fluent helper, data objects, or facade."
weight: 1
---

## Record an event

Pass an event to the `audit_log` helper, add the attributes you need, and call `record()`:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.published')
    ->tenant('org_123')
    ->record();
```

The event may be a string or a backed enum.

## Add attributes

Use the fluent builder to describe the event and its context:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.published')
    ->tenant('org_123')
    ->actor(type: 'user', id: 'member_123')
    ->target(type: 'document', id: 'doc_123')
    ->metadata(['visibility' => 'public'])
    ->record();
```

Optional fields include `description`, `bucket`, `source`, `occurredAt`, `remoteIp`, `userAgent`, `id`, and `correlationId`. Tenant, actor, and target IDs may be integers or strings.

Call `target()` more than once when an event affects multiple resources. Actor and target types, buckets, and sources also accept backed enums.

## Recording prepared logs

Call `toAuditLogData()` when a log must be prepared before it is recorded. Calling `audit_log()` without an event returns the manager that records prepared data:

```php
use function HosmelQ\AuditLog\audit_log;

$log = audit_log('document.published')
    ->tenant('org_123')
    ->target(type: 'document', id: 'doc_123')
    ->toAuditLogData();

audit_log()->record($log);
```

You may also record an `AuditLogData` instance through the facade:

```php
use HosmelQ\AuditLog\Facades\AuditLog;

AuditLog::record($log);
```

The manager accepts one `AuditLogData` instance or an iterable. See [batching and correlating logs](../advanced-usage/correlating-logs) for multi-log behavior.

## Understand default values

Missing values follow these rules:

- If no actor is set, a system actor is used.
- If no bucket or source is set, the configured default is used.
- If no ID is set, one is generated.
- If no occurrence time is set, the current time is used.
- If request metadata is not provided, request capture may fill it.

## Use enums

Backed enum values are stored using their string or integer backing value converted to a string:

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

For reusable model-backed identities, see [actors and targets](audit-log-identities).
