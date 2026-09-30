<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests\TestSupport;

enum TestIdentityType: string
{
    case Organization = 'organization';
    case User = 'user';
}
