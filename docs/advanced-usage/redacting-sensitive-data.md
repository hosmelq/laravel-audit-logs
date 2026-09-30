---
title: "Redacting Sensitive Data"
description: "Exclude or mask metadata fields and omit request metadata for individual events."
weight: 4
---

## Configure metadata rules

Set the metadata keys that should be excluded or masked in `config/audit-log.php`:

```php
'redaction' => [
    'exclude' => ['password', 'token'],
    'mask' => ['email'],
    'replacement' => '[REDACTED]',
],
```

Rules apply to metadata belonging to the event, its actor, and every target. Keys are matched exactly and are case-sensitive. An excluded key is removed; a masked key keeps its name and receives the replacement string. Exclusion takes precedence when a key appears in both lists.

The same rules apply to attribute names in [recorded changes](../basic-usage/recording-changes).

Both lists are empty by default. Rules affect logs when they are recorded and do not update existing rows. Actor and target IDs, types, names, and the event description are preserved, so avoid placing secrets in those fields.

## Omit request metadata

Call `withoutRequestMetadata()` when an event should not include a remote IP or user agent:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.published')
    ->tenant('org_123')
    ->withoutRequestMetadata()
    ->record();
```

The option omits both values, including values provided explicitly. Prepared `AuditLogData` objects may set `captureRequestMetadata: false` for the same behavior.

## Test redacted logs

`AuditLog::fake()` applies the same redaction rules as database storage. Assertions inspect the redacted payload. The original prepared data object is preserved.
