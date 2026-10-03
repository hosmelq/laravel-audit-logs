---
title: "Recording Audit Logs"
description: "Record audit events with the builder, the facade, or data objects."
weight: 1
---

## Record an event

Pass an event name to the `audit_log` helper, add the attributes you need, and call `record()`:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.published')
    ->tenant('org_123')
    ->actor(type: 'user', id: 'member_123')
    ->target(type: 'document', id: 'doc_123')
    ->metadata(['visibility' => 'public'])
    ->record();
```

`audit_log('...')` returns a builder. `record()` prepares the log and writes it to the database immediately.

## Builder methods

| Method | Description |
| --- | --- |
| `tenant($id)` | Sets the tenant. Accepts an integer or string. |
| `actor($type, $id, $name, $metadata)` | Sets the actor, replacing any earlier actor. See [actors and targets](audit-log-identities). |
| `target($type, $id, $name, $metadata)` | Adds a target. Call it once per affected resource. |
| `metadata($metadata)` | Sets the event metadata, replacing any earlier metadata. |
| `changes($before, $after)` | Records the attributes that changed. See [attribute changes](recording-changes). |
| `description($description)` | Sets a human-readable description. |
| `bucket($bucket)` | Sets the bucket. Accepts a string or backed enum. |
| `source($source)` | Sets the source. Accepts a string or backed enum. |
| `occurredAt($occurredAt)` | Sets when the event happened. Accepts any `CarbonInterface` instance. |
| `id($id)` | Sets the log ID instead of generating one. |
| `correlationId($correlationId)` | Links the log to related logs. See [correlating logs](../advanced-usage/correlating-logs). |
| `remoteIp($remoteIp)` | Sets the remote IP instead of reading it from the request. |
| `userAgent($userAgent)` | Sets the user agent instead of reading it from the request. |
| `withoutRequestMetadata()` | Stores no remote IP or user agent. See [redacting sensitive data](../advanced-usage/redacting-sensitive-data#omit-request-metadata). |
| `toAuditLogData()` | Returns the prepared `AuditLogData` object without recording it. |
| `record()` | Prepares and records the log. |

Metadata is an array with string keys and string, integer, float, boolean, or null values. Tenant, actor, and target IDs are stored as strings, so `123` and `'123'` are equivalent.

## Use enums

The event, bucket, source, and actor and target types accept backed enums. The enum's backing value is stored as a string:

```php
use function HosmelQ\AuditLog\audit_log;

enum AuditEvent: string
{
    case DocumentPublished = 'document.published';
}

audit_log(AuditEvent::DocumentPublished)
    ->tenant('org_123')
    ->record();
```

## Default values

The builder fills attributes you do not set when it prepares the log:

| Attribute | Default |
| --- | --- |
| Actor | The [actor resolver](../advanced-usage/configuring-context) result, or a system actor with the ID `system`, type `system`, and name `System`. |
| Tenant | The [tenant resolver](../advanced-usage/configuring-context) result, or an empty string. |
| Bucket and source | The `audit-log.defaults.bucket` and `audit-log.defaults.source` options. |
| ID | A generated ID such as `log_01J9Z3K8V2Q4X6M8N0P2R4T6W8`. |
| Occurrence time | The time the log is prepared. |
| Remote IP and user agent | The current request, as described below. |
| Description | An empty string. |
| Metadata and targets | Empty. |

A log has no correlation ID unless you set one, record it in a batch, or record it inside a correlation scope.

## Request metadata

When the builder prepares a log, it reads a missing remote IP and user agent from the current request. Values passed to `remoteIp()` and `userAgent()` are kept. The `audit-log.request` options control whether each value is captured and whether capture happens in the console, including queue workers. See [configuration](../configuration#request-metadata).

Because the values are read when the log is prepared, a log prepared with `toAuditLogData()` during a request keeps that request's values even if it is recorded later.

## Record data objects

Call `audit_log()` without an event, or use the `AuditLog` facade, to get the audit log manager. The manager's `record()` method accepts one `AuditLogData` object or an iterable of them:

```php
use HosmelQ\AuditLog\Facades\AuditLog;

use function HosmelQ\AuditLog\audit_log;

$log = audit_log('document.published')
    ->tenant('org_123')
    ->target(type: 'document', id: 'doc_123')
    ->toAuditLogData();

audit_log()->record($log);

// Or through the facade:
AuditLog::record($log);
```

You may also create an `AuditLogData` object directly. Pass arguments by name:

```php
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Data\AuditLogTargetData;
use HosmelQ\AuditLog\Facades\AuditLog;

AuditLog::record(new AuditLogData(
    actor: new AuditLogActorData(id: 'member_123', type: 'user'),
    bucket: 'application',
    event: 'document.published',
    source: 'platform',
    targets: [new AuditLogTargetData(id: 'doc_123', type: 'document')],
    tenantId: 'org_123',
));
```

The `actor`, `bucket`, `event`, and `source` arguments are required, and `event` must be a string. Configured defaults and context resolvers are not applied to data objects you create yourself. The ID and occurrence time are generated when the object is created. A missing remote IP and user agent are read from the current request when the log is recorded, unless `captureRequestMetadata` is `false`.

See [correlating logs](../advanced-usage/correlating-logs) for recording several logs in one call.

## Customize generated IDs

Log IDs use the `log_` prefix and correlation IDs use the `cor_` prefix, each followed by a ULID. To use a different format, extend `HosmelQ\AuditLog\AuditLogId` and bind your class in a service provider's `register()` method:

```php
namespace App\Support;

use HosmelQ\AuditLog\AuditLogId;
use Illuminate\Support\Str;

class UuidAuditLogId extends AuditLogId
{
    public function correlation(): string
    {
        return (string) Str::uuid7();
    }

    public function log(): string
    {
        return (string) Str::uuid7();
    }
}
```

```php
use App\Support\UuidAuditLogId;
use HosmelQ\AuditLog\AuditLogId;

$this->app->scoped(AuditLogId::class, UuidAuditLogId::class);
```

IDs set explicitly with `id()` or `correlationId()` are not affected.
