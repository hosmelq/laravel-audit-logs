---
title: "Configuring Audit Context"
description: "Resolve the default actor and tenant from your application."
weight: 1
---

Context resolvers supply the actor and tenant for logs that do not set them. Register them once instead of calling `actor()` and `tenant()` for every event.

## Register resolvers

Register the resolvers in the `boot()` method of a service provider:

```php
namespace App\Providers;

use App\Models\User;
use HosmelQ\AuditLog\AuditLogContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        AuditLogContext::resolveActorUsing(fn (): ?User => Auth::user());
        AuditLogContext::resolveTenantUsing(fn (): ?int => Auth::user()?->current_team_id);
    }
}
```

In this example, `User` implements [`HasAuditLogIdentity`](../basic-usage/audit-log-identities#reuse-application-objects).

The actor resolver may return:

- An object implementing `HasAuditLogIdentity`.
- An `AuditLogIdentity` or `AuditLogActorData` object.
- `null`, which uses the system actor with the ID `system`, type `system`, and name `System`.

The tenant resolver may return an integer, a string, or `null`. A `null` tenant is stored as an empty string.

Pass `null` to `resolveActorUsing()` or `resolveTenantUsing()` to remove a resolver.

## When resolvers run

The builder calls a resolver when `toAuditLogData()` or `record()` prepares a log that has no actor or tenant:

- Values set with `actor()` and `tenant()` take precedence, and the resolver is not called. This includes values such as an empty string or `0`.
- Resolvers run again for every log. Their results are not cached.
- Exceptions thrown by a resolver are not caught and propagate to the code recording the log.
- `AuditLogData` objects you create yourself are recorded as provided. Resolvers do not change them.

## Octane and queue workers

Resolvers are stored for the lifetime of the PHP process, not per request. Read the current user or tenant inside the closure, as in the example above, so each log uses the values of the current request or job. Do not capture a request, user, or tenant object when registering the resolver.

Queued jobs usually have no authenticated user, so the actor resolver returns `null` and logs use the system actor unless the job sets an actor explicitly.
