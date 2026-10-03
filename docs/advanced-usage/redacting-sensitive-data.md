---
title: "Redacting Sensitive Data"
description: "Exclude or mask metadata keys and omit request metadata for individual events."
weight: 2
---

## Configure redaction rules

List the metadata keys to exclude or mask in `config/audit-log.php`:

```php
'redaction' => [
    'exclude' => ['password', 'token'],
    'mask' => ['email'],
    'replacement' => '[REDACTED]',
],
```

When a log is recorded:

- An excluded key is removed.
- A masked key keeps its name, and its value is replaced with the `replacement` string. Masking does not add keys that are missing.
- A key listed in both `exclude` and `mask` is excluded.

Rules apply to the event metadata, the actor metadata, the metadata of every target, and the attribute names in [attribute changes](../basic-usage/recording-changes#redact-changes). Both lists are empty by default, so metadata is stored as provided.

## How keys are matched

- Keys are matched exactly and are case-sensitive. A `password` rule does not match `Password`.
- Only top-level keys are matched. Values inside nested arrays are not inspected.
- Actor and target IDs, types, and names, and the event description, are never redacted. Do not put secrets in those fields.

Redaction happens when a log is recorded. Changing the rules does not update rows that already exist.

## Omit request metadata

Call `withoutRequestMetadata()` when a log should not store a remote IP or user agent:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('auth.password.reset')
    ->tenant('org_123')
    ->withoutRequestMetadata()
    ->record();
```

Both values are omitted, including values set with `remoteIp()` and `userAgent()`. To disable capture for every log instead, use the `audit-log.request` [configuration options](../configuration#request-metadata).

For `AuditLogData` objects you create yourself, pass `captureRequestMetadata: false` for the same result.

## Testing

`AuditLog::fake()` applies the same rules before storing logs in memory, so assertions see the redacted values. See [testing audit logs](../basic-usage/testing).
