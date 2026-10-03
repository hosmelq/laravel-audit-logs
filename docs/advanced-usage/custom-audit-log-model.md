---
title: "Customizing the Audit Log Model"
description: "Add application-specific scopes, relationships, or casts to stored logs."
weight: 4
---

Create a custom model when your application needs reusable query scopes, relationships, or additional casts.

## Extend the package model

The custom model must extend `HosmelQ\AuditLog\Models\AuditLog`:

```php
namespace App\Models;

use HosmelQ\AuditLog\Models\AuditLog as BaseAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends BaseAuditLog
{
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'tenant_id');
    }

    public function scopeForEvent(Builder $query, string $event): void
    {
        $query->where('event', $event);
    }
}
```

The base model reads the connection and table from the `audit-log.storage` options, casts the JSON and date columns, has no `created_at` or `updated_at` timestamps, and supports [pruning](../configuration#retention).

## Register the model

Register the class in the `boot()` method of a service provider:

```php
namespace App\Providers;

use App\Models\AuditLog;
use HosmelQ\AuditLog\DatabaseAuditLogManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        DatabaseAuditLogManager::useModel(AuditLog::class);
    }
}
```

Registering a class that does not extend the package model throws an `InvalidArgumentException`.

The database manager uses the registered model's connection and table when it writes logs. Logs are inserted with the query builder, so model events, mutators, and casts are not applied when writing.

Query your own class to use its scopes and relationships:

```php
use App\Models\AuditLog;

$logs = AuditLog::query()->forEvent('document.published')->with('team')->get();
```
