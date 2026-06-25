<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests\TestSupport;

use HosmelQ\AuditLog\Contracts\HasAuditLogIdentity;
use HosmelQ\AuditLog\Support\AuditLogIdentity;

final class TestUser implements HasAuditLogIdentity
{
    public function auditLogIdentity(): AuditLogIdentity
    {
        return new AuditLogIdentity(
            id: 'user-1',
            type: 'user',
            metadata: ['role' => 'admin'],
            name: 'user@example.com',
        );
    }
}
