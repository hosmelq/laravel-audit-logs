---
title: "Recording Attribute Changes"
description: "Record the previous and current values of changed attributes."
weight: 3
---

## Record changes

Pass the previous and current attribute values to `changes()`:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.updated')
    ->tenant('org_123')
    ->target(type: 'document', id: 'doc_123')
    ->changes(
        before: ['title' => 'Draft', 'published' => false],
        after: ['title' => 'Release notes', 'published' => true],
    )
    ->record();
```

The builder compares values strictly and keeps only changed attributes. Unchanged values are omitted. An added attribute appears only in `after`; a removed attribute appears only in `before`. A missing attribute is different from one whose value is `null`.

Values may be strings, integers, floats, booleans, null, or JSON-compatible arrays. `false`, `0`, and empty strings are preserved. Normalize dates and other objects before providing them.

## Prepare change data

Use `AuditLogChangesData::between()` when constructing an `AuditLogData` object directly:

```php
use HosmelQ\AuditLog\Data\AuditLogChangesData;

$changes = AuditLogChangesData::between(
    before: ['visibility' => 'private'],
    after: ['visibility' => 'public'],
);
```

Pass the result as the `changes` argument. The constructor of `AuditLogChangesData` also accepts prepared `before` and `after` arrays when the changed attributes have already been selected.

## Read stored changes

The audit log model casts the `changes` column to an array containing `before` and `after`. Logs recorded without change data have a null value.

Metadata redaction rules also apply to attribute names in both arrays. Excluded attributes are removed, and masked values receive the configured replacement. A masked attribute remains in the change data even when both displayed values are the same replacement. Rules match attribute names, so exclude or mask the entire attribute when its value is a nested array containing sensitive data.

The fake exposes the same redacted change data through `$log->changes`.
