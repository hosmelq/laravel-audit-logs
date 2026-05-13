<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests\TestSupport;

enum AuditLogEvent: string
{
    case DocumentPublished = 'document.publish';
}
