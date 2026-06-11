<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests\TestSupport;

enum TestEvent: string
{
    case AccountClosed = 'account.closed';
    case AccountUpdated = 'account.updated';
}
