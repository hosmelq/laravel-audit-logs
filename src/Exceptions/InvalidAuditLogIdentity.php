<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Exceptions;

use InvalidArgumentException;

final class InvalidAuditLogIdentity extends InvalidArgumentException
{
    public static function missingActorId(): self
    {
        return new self('An actor id is required when the actor is not an audit log identity.');
    }

    public static function missingTargetId(): self
    {
        return new self('A target id is required when the target is not an audit log identity.');
    }
}
