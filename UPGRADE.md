# Upgrade Guide

## Upgrading to 3.0 from 2.x

### Laravel 13

Laravel Audit Logs now requires Laravel 13. Upgrade your application to Laravel 13 before updating
the package.

### Published configuration

The installer publishes `config/audit-log.php`, and the published file takes precedence over the
package's defaults. If you published it, pass the retention value through unchanged, so an empty
value keeps logs indefinitely and a value that is not a whole number of days throws instead of
making new logs expire as soon as they occur:

```php
'retention' => [
    'days' => env('AUDIT_LOG_RETENTION_DAYS'),
],
```

If your application caches its configuration, rebuild the cache afterwards:

```bash
php artisan config:cache
```

## Upgrading to 2.0 from 1.x

### PHP 8.4

Laravel Audit Logs now requires PHP 8.4 or newer.

### Attribute changes migration

Version 2.0 adds a nullable `attribute_changes` column to the configured audit log table. Publish the new
migration and run it before recording logs with the updated package:

```bash
php artisan vendor:publish --tag="audit-log-migrations"
php artisan migrate
```

Existing logs are preserved and have a null value.

### `AuditLogData` constructor

The constructor accepts the new `captureRequestMetadata` and `changes` arguments after `source`,
and `occurredAt` now follows `metadata`. Pass arguments by name when creating `AuditLogData`
objects directly.
