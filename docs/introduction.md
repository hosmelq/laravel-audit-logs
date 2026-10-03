---
title: "Introduction"
description: "Record structured, queryable audit events in Laravel applications."
weight: 1
---

Laravel Audit Logs records application audit events as structured database rows. Use it for meaningful domain actions, such as publishing a document, changing account settings, or performing an administrative action.

Each audit log can store:

- An event name, such as `document.published`.
- The tenant the event belongs to.
- The actor who performed the event and the targets it affected.
- Custom metadata and the attribute changes made by the event.
- The remote IP and user agent of the current request.
- A bucket and source for grouping logs, and a correlation ID that links related logs.

Stored logs are read through an Eloquent model, so you can query them like any other table in your application.

## Quick start

After [installing the package](installation), record an event with the `audit_log` helper:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.published')
    ->tenant('org_123')
    ->actor(type: 'user', id: 'member_123')
    ->target(type: 'document', id: 'doc_123')
    ->record();
```

Query stored logs with the package model:

```php
use HosmelQ\AuditLog\Models\AuditLog;

$logs = AuditLog::query()
    ->where('tenant_id', 'org_123')
    ->where('event', 'document.published')
    ->get();
```

## Next steps

- [Recording audit logs](basic-usage/recording-audit-logs) covers every attribute a log can have.
- [Configuring audit context](advanced-usage/configuring-context) fills the actor and tenant from your application.
- [Redacting sensitive data](advanced-usage/redacting-sensitive-data) keeps secrets out of stored metadata.
- [Testing audit logs](basic-usage/testing) replaces the database with an in-memory fake.
