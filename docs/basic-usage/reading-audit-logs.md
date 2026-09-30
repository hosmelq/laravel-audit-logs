---
title: "Querying Audit Logs"
description: "Query stored events and work with their structured attributes."
weight: 4
---

## Query with Eloquent

Use the package model like any other Eloquent model:

```php
use HosmelQ\AuditLog\Models\AuditLog;

$logs = AuditLog::query()
    ->forTenant('org_123')
    ->inBucket('application')
    ->latest('occurred_at')
    ->orderByDesc('id')
    ->get();
```

The model uses the configured database connection and table.

## Filter events

Combine the package's query scopes with normal Eloquent methods:

```php
$logs = AuditLog::query()
    ->forTenant('org_123')
    ->forActor(type: 'user', id: 'member_123')
    ->forEvent('document.published')
    ->fromSource('platform')
    ->latest('occurred_at')
    ->orderByDesc('id')
    ->cursorPaginate();
```

| Scope | Filters |
| --- | --- |
| `forTenant($id)` | The tenant ID, normalized to a string. |
| `forActor($type, $id)` | Both the actor type and ID. |
| `forTarget($type, $id)` | A target containing both the type and ID. |
| `forEvent($event)` | The event name. |
| `forCorrelation($id)` | The correlation ID, or uncorrelated logs when the value is null. |
| `inBucket($bucket)` | The bucket. |
| `fromSource($source)` | The source. |

Actor and target types, events, buckets, and sources accept backed enums. Actor, target, and tenant IDs accept integers or strings. Include `forTenant()` when listing events for a particular tenant.

## Find resource history

Pass a type and ID or an object implementing `HasAuditLogIdentity`:

```php
$logs = AuditLog::query()
    ->forTenant('org_123')
    ->forTarget(type: 'document', id: 'doc_123')
    ->get();

$logs = AuditLog::query()->forActor($user)->get();
$logs = AuditLog::query()->forTarget($document)->get();
```

An ID is required when the actor or target is provided as a type. The target scope matches type and ID within the same target, including events with multiple targets. Names and metadata do not affect the match.

Target filtering uses JSON containment on MySQL, MariaDB, and PostgreSQL, and a JSON row query on SQLite and SQL Server.

## Work with stored values

The following columns are cast automatically:

- `actor_metadata`, `metadata`, and `targets` are arrays.
- `changes` is an array containing `before` and `after`, or null when no changes were provided.
- `occurred_at`, `inserted_at`, and `expires_at` are immutable dates.

Common indexed filters include `tenant_id`, `bucket`, `event`, `actor_id`, `actor_type`, `correlation_id`, and the date columns. The composite index on `tenant_id`, `bucket`, `occurred_at`, and `id` supports tenant-scoped chronological queries.

Extend the package model when your application needs additional scopes, relationships, or casts. See [customizing the audit log model](../advanced-usage/custom-audit-log-model).
