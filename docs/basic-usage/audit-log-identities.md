---
title: "Actors and Targets"
description: "Describe who performed an event and which resources it affected."
weight: 2
---

The actor identifies who performed an event. Targets identify the resources the event affected. A log has one actor and any number of targets.

Actors and targets share the same attributes:

| Attribute | Description |
| --- | --- |
| `type` | The kind of identity, such as `user` or `document`. Accepts a string or backed enum. |
| `id` | The identifier. Accepts an integer or string and is stored as a string. |
| `name` | An optional display name. |
| `metadata` | Optional metadata, using the same format as event metadata. |

## Define identities inline

Pass the attributes directly when the identity is only needed for one log:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.published')
    ->actor(type: 'user', id: 'member_123', name: 'Jane Doe')
    ->target(
        type: 'document',
        id: 'doc_123',
        metadata: ['visibility' => 'public'],
    )
    ->target(type: 'folder', id: 'folder_456')
    ->record();
```

`actor()` replaces any actor set earlier, while each `target()` call adds another target. An inline actor or target without an ID throws a `HosmelQ\AuditLog\Exceptions\InvalidAuditLogIdentity` exception.

If no actor is set, the builder uses the [actor resolver](../advanced-usage/configuring-context) or the system actor.

## Reuse application objects

Implement `HasAuditLogIdentity` when a model or value object should define its audit identity once:

```php
namespace App\Models;

use HosmelQ\AuditLog\Contracts\HasAuditLogIdentity;
use HosmelQ\AuditLog\Support\AuditLogIdentity;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasAuditLogIdentity
{
    public function auditLogIdentity(): AuditLogIdentity
    {
        return new AuditLogIdentity(
            id: $this->id,
            type: $this->getMorphClass(),
            name: $this->email,
        );
    }
}
```

`AuditLogIdentity` takes the same `id`, `type`, `name`, and `metadata` attributes. `id` and `type` are required.

Pass the object as the first argument to `actor()` or `target()`:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('organization.member.removed')
    ->actor($user)
    ->target($organization)
    ->target($member)
    ->tenant($organization->id)
    ->record();
```

When you pass an object, the identity comes entirely from `auditLogIdentity()`. Any `id`, `name`, or `metadata` arguments in the same call are ignored.
