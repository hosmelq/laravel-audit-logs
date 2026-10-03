---
title: "Recording Attribute Changes"
description: "Store the previous and current values of the attributes an event changed."
weight: 3
---

## Record changes

Pass the attribute values from before and after the event to `changes()`:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.updated')
    ->tenant('org_123')
    ->target(type: 'document', id: 'doc_123')
    ->changes(
        before: ['title' => 'Draft', 'visibility' => 'private', 'published' => false],
        after: ['title' => 'Release notes', 'visibility' => 'private', 'published' => true],
    )
    ->record();
```

Only changed attributes are stored. The log above stores:

```php
[
    'before' => ['title' => 'Draft', 'published' => false],
    'after' => ['title' => 'Release notes', 'published' => true],
]
```

You may pass complete snapshots of a record, or only the attributes you care about.

## How values are compared

Values are compared with strict comparison (`===`):

- `1` and `'1'`, `1` and `1.0`, `0` and `false`, and `''` and `null` are all different values.
- An attribute that exists only in `after` is stored only in `after`. An attribute that exists only in `before` is stored only in `before`.
- A missing attribute is different from an attribute whose value is `null`.
- Array values are compared as a whole. If any part of a nested array differs, the complete array is stored on both sides.

Values may be strings, integers, floats, booleans, null, or arrays of those values. Convert dates, enums, and other objects to one of these types first. Values such as `false`, `0`, and empty strings are stored as provided, and floats keep their type when read back.

When nothing changed, the log stores no change data, the same as a log recorded without `changes()`.

## Record Eloquent changes

Take the snapshot before updating a model, then compare it with the updated values:

```php
use function HosmelQ\AuditLog\audit_log;

$attributes = ['title', 'visibility'];
$before = $document->only($attributes);

$document->update($validated);

audit_log('document.updated')
    ->tenant($document->organization_id)
    ->target(type: 'document', id: $document->id)
    ->changes(before: $before, after: $document->only($attributes))
    ->record();
```

`only()` returns cast values, so convert attributes cast to dates, enums, or objects before passing them.

## Use data objects

When you create an `AuditLogData` object directly, build its `changes` argument with `AuditLogChangesData::between()`. It applies the same comparison as the builder:

```php
use HosmelQ\AuditLog\Data\AuditLogChangesData;

$changes = AuditLogChangesData::between(
    before: ['visibility' => 'private'],
    after: ['visibility' => 'public'],
);
```

`new AuditLogChangesData(before: [...], after: [...])` stores both arrays as provided, without comparing them. Use it when you have already selected the changed attributes.

## Read stored changes

The model casts the `attribute_changes` column to an array with `before` and `after` keys:

```php
use HosmelQ\AuditLog\Models\AuditLog;

$log = AuditLog::query()->where('event', 'document.updated')->latest('occurred_at')->firstOrFail();

$log->attribute_changes['before']; // ['title' => 'Draft', 'published' => false]
$log->attribute_changes['after'];  // ['title' => 'Release notes', 'published' => true]
```

The column is `null` for logs recorded without `changes()` and for logs whose comparison found no changed attributes.

## Redact changes

[Redaction rules](../advanced-usage/redacting-sensitive-data) also apply to attribute names in `before` and `after`. Excluded attributes are removed. Masked attributes keep their name and receive the replacement value wherever they appear, even when the stored `before` and `after` values become identical. If redaction removes every changed attribute, the log stores no change data.

Rules match top-level attribute names only. To hide a value inside a nested array, exclude or mask the whole attribute.
