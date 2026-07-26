---
title: "Customizing the Audit Log Model"
description: "Add application-specific scopes, relationships, or casts to stored logs."
weight: 2
---

Create a custom model when your application needs reusable query scopes, relationships, or additional casts.

## Extend the package model

The custom model must extend `HosmelQ\AuditLog\Models\AuditLog`:

```php
namespace App\Models;

use HosmelQ\AuditLog\Models\AuditLog as BaseAuditLog;

class AuditLog extends BaseAuditLog
{
}
```

The base model already provides the configured connection and table, casts structured values, disables timestamps, and supports mass pruning.

## Register the model

Register the class once while your application boots:

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

The registered model is used when audit logs are written. Query the same class when consuming your application-specific scopes or relationships.
