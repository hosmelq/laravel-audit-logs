---
title: "Configuring Retention"
description: "Set retention periods for individual events, event names, or buckets."
weight: 5
---

## Configure retention rules

Set the default period and any event or bucket rules in `config/audit-log.php`:

```php
'retention' => [
    'days' => 30,
    'events' => [
        'auth.sessions.delete' => 365,
        'document.published' => null,
    ],
    'buckets' => [
        'security' => 180,
    ],
],
```

Each value must be a non-negative integer or null. Rules match the complete event name or bucket exactly, including names containing dots. Wildcards are not supported.

## Override an individual log

Call `retentionDays()` to override the configured rules for one log:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.published')
    ->tenant('org_123')
    ->retentionDays(90)
    ->record();
```

Pass `null` to keep the log indefinitely. Pass `0` to expire it at its occurrence time.

Prepared `AuditLogData` objects may provide `retention: new AuditLogRetentionData(days: 90)`. An omitted retention object uses the configured rules; an object with `days: null` explicitly keeps the log indefinitely.

## Understand precedence

The writer selects the first applicable rule:

1. The individual log's explicit retention period.
2. The rule matching its event name.
3. The rule matching its bucket.
4. The global `retention.days` value.

A matching null or zero value is used directly. It does not fall through to another rule.

## Prune expired logs

Expiration is calculated from `occurred_at` when the log is written. Changes to configuration do not update existing rows. Historical events may already be expired when inserted.

Schedule Laravel's `model:prune` command as described in [configuration](../configuration). Only logs with a non-null, expired `expires_at` value are pruned.
