---
title: "Configuring Audit Context"
description: "Resolve default actors and tenants from your application."
weight: 3
---

Use context resolvers when your application should provide the actor or tenant for events recorded through the fluent builder.

## Register resolvers

Register the callbacks in your application's service provider `boot()` method:

```php
use HosmelQ\AuditLog\AuditLogContext;
use HosmelQ\AuditLog\Contracts\HasAuditLogIdentity;

AuditLogContext::resolveActorUsing(fn (): ?HasAuditLogIdentity => auth()->user());
AuditLogContext::resolveTenantUsing(fn (): ?string => app(CurrentTenant::class)->id());
```

The actor resolver may return an `AuditLogActorData`, `AuditLogIdentity`, an object implementing `HasAuditLogIdentity`, or `null`. The tenant resolver may return an integer, string, or `null`.

## Understand default values

The fluent builder invokes resolvers for missing values when `toAuditLogData()` or `record()` is called:

- Explicit actors and tenants take precedence, including an empty tenant or zero ID.
- A null actor falls back to the system actor.
- A null tenant becomes an empty string.
- Prepared `AuditLogData` objects keep their explicit context.

Pass `null` to either registration method to clear its resolver.

## Use long-running workers

The context instance is scoped to the current request or job. Registered callbacks remain available across scopes, and their results are evaluated for each log.

Resolve the current user and tenant inside the callback when using Octane or queue workers. Avoid capturing request-specific objects during registration. Register callbacks in a service provider so Laravel's configuration cache remains usable.
