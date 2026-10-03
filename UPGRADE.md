# Upgrade Guide

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
