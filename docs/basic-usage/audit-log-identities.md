---
title: "Actors and Targets"
description: "Describe who performed an event and which resources it affected."
weight: 2
---

Actors identify who performed an event. Targets identify the resources affected by it.

## Define identities inline

Pass a type and ID directly when the identity is only needed for one log. Names and metadata are optional:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('document.published')
    ->actor(type: 'user', id: 'member_123', name: 'Jane Doe')
    ->target(
        type: 'document',
        id: 'doc_123',
        metadata: ['visibility' => 'public'],
    )
    ->record();
```

An ID is required for every inline actor or target. Call `target()` repeatedly when an event affects multiple resources.

## Reuse application objects

Implement `HasAuditLogIdentity` when a model or value object should define its audit identity once:

```php
use HosmelQ\AuditLog\Contracts\HasAuditLogIdentity;
use HosmelQ\AuditLog\Support\AuditLogIdentity;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements HasAuditLogIdentity
{
    public function auditLogIdentity(): AuditLogIdentity
    {
        return new AuditLogIdentity(
            id: $this->id,
            name: $this->email,
            type: $this->getMorphClass(),
        );
    }
}
```

Pass the object directly to `actor()` or `target()`:

```php
use function HosmelQ\AuditLog\audit_log;

audit_log('auth.sessions.delete')
    ->actor($user)
    ->target($organization)
    ->tenant($organization->id)
    ->record();
```

`AuditLogIdentity` accepts a string or integer ID, a string or backed-enum type, optional metadata, and an optional display name.
