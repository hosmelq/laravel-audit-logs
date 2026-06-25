<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests\TestSupport;

use HosmelQ\AuditLog\Contracts\HasAuditLogIdentity;
use HosmelQ\AuditLog\Support\AuditLogIdentity;

final class TestOrganization implements HasAuditLogIdentity
{
    public function auditLogIdentity(): AuditLogIdentity
    {
        return new AuditLogIdentity(
            id: 'organization-1',
            type: 'organization',
            metadata: ['plan' => 'pro'],
            name: 'Acme',
        );
    }
}
