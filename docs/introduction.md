---
title: "Introduction"
description: "Record structured, queryable audit events in Laravel applications."
weight: 1
---

Laravel Audit Logs provides a simple, fluent API for recording application audit events. You may include actors, targets, request metadata, and custom metadata for each event.

Use audit logs for meaningful domain actions such as publishing a document, changing account settings, or performing an administrative action. Each event is stored as a structured database record that you can query with Eloquent.

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
