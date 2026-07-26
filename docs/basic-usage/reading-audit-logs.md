---
title: "Querying Audit Logs"
description: "Query stored events and work with their structured attributes."
weight: 3
---

## Query with Eloquent

Use the package model like any other Eloquent model:

```php
use HosmelQ\AuditLog\Models\AuditLog;

$logs = AuditLog::query()
    ->where('tenant_id', 'org_123')
    ->where('bucket', 'application')
    ->latest('occurred_at')
    ->get();
```

The model uses the configured database connection and table.

## Work with stored values

The following columns are cast automatically:

- `actor_metadata`, `metadata`, and `targets` are arrays.
- `occurred_at`, `inserted_at`, and `expires_at` are immutable dates.

Common indexed filters include `tenant_id`, `bucket`, `event`, `actor_id`, `actor_type`, `correlation_id`, and the date columns. The composite index on `tenant_id`, `bucket`, `occurred_at`, and `id` supports tenant-scoped chronological queries.

Extend the package model when your application needs reusable scopes, relationships, or additional casts. See [customizing the audit log model](../advanced-usage/custom-audit-log-model).
