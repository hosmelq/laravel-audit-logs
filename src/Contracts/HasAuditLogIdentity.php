<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Contracts;

use HosmelQ\AuditLog\Support\AuditLogIdentity;

interface HasAuditLogIdentity
{
    public function auditLogIdentity(): AuditLogIdentity;
}
