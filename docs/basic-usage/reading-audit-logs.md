---
title: "Querying Audit Logs"
description: "Query stored logs with Eloquent and work with their columns."
weight: 4
---

## Query with Eloquent

Query stored logs with the `HosmelQ\AuditLog\Models\AuditLog` model:

```php
use HosmelQ\AuditLog\Models\AuditLog;

$logs = AuditLog::query()
    ->where('tenant_id', 'org_123')
    ->where('bucket', 'application')
    ->latest('occurred_at')
    ->get();
```

The model uses the connection and table from the `audit-log.storage` options. To add scopes or relationships, [extend the model](../advanced-usage/custom-audit-log-model).

## Columns

| Column | Type | Description |
| --- | --- | --- |
| `id` | string | Log ID. |
| `event` | string | Event name. |
| `tenant_id` | string | Tenant ID, or an empty string. |
| `actor_id` | string | Actor ID. |
| `actor_type` | string | Actor type. |
| `actor_name` | string or null | Actor display name. |
| `actor_metadata` | array | Actor metadata. |
| `targets` | array | List of targets. Each target has `id`, `type`, `name`, and `metadata` keys. |
| `metadata` | array | Event metadata. |
| `attribute_changes` | array or null | Changed attributes under `before` and `after` keys. See [attribute changes](recording-changes). |
| `description` | string | Description, or an empty string. |
| `bucket` | string | Bucket. |
| `source` | string | Source. |
| `correlation_id` | string or null | Correlation ID shared by related logs. |
| `remote_ip` | string or null | Remote IP of the request. |
| `user_agent` | string or null | User agent of the request. |
| `occurred_at` | `CarbonImmutable` | When the event happened. |
| `inserted_at` | `CarbonImmutable` | When the log was written. |
| `expires_at` | `CarbonImmutable` or null | When the log becomes eligible for [pruning](../configuration#retention). |

Array columns are stored as JSON and cast to PHP arrays. Date columns are stored with millisecond precision.

## Indexes

The table has single-column indexes on `event`, `actor_id`, `actor_type`, `correlation_id`, `occurred_at`, `inserted_at`, and `expires_at`. A composite index on `tenant_id`, `bucket`, `occurred_at`, and `id` supports listing a tenant's logs in chronological order. Filter by `tenant_id` first to use it.

## Query JSON columns

Use Laravel's JSON query methods to filter by metadata or targets:

```php
use HosmelQ\AuditLog\Models\AuditLog;

$logs = AuditLog::query()
    ->where('tenant_id', 'org_123')
    ->where('metadata->visibility', 'public')
    ->whereJsonContains('targets', [['type' => 'document', 'id' => 'doc_123']])
    ->get();
```

`whereJsonContains()` requires a database that supports JSON containment, such as MySQL or PostgreSQL. JSON columns are not indexed, so combine these filters with an indexed column.
