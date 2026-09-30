<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use Closure;
use HosmelQ\AuditLog\Contracts\HasAuditLogIdentity;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Support\AuditLogIdentity;

final class AuditLogContext
{
    /**
     * @var null|(Closure(): (null|AuditLogActorData|AuditLogIdentity|HasAuditLogIdentity))
     */
    private static null|Closure $actorResolver = null;

    /**
     * @var null|(Closure(): (null|int|string))
     */
    private static null|Closure $tenantResolver = null;

    /**
     * @param null|(Closure(): (null|AuditLogActorData|AuditLogIdentity|HasAuditLogIdentity)) $resolver
     */
    public static function resolveActorUsing(null|Closure $resolver): void
    {
        self::$actorResolver = $resolver;
    }

    /**
     * @param null|(Closure(): (null|int|string)) $resolver
     */
    public static function resolveTenantUsing(null|Closure $resolver): void
    {
        self::$tenantResolver = $resolver;
    }

    public function actor(): AuditLogActorData
    {
        $actor = self::$actorResolver instanceof Closure ? (self::$actorResolver)() : null;

        if ($actor instanceof HasAuditLogIdentity) {
            return $actor->auditLogIdentity()->toActorData();
        }

        if ($actor instanceof AuditLogIdentity) {
            return $actor->toActorData();
        }

        return $actor ?? new AuditLogActorData(id: 'system', name: 'System', type: 'system');
    }

    public function tenant(): string
    {
        return (string) (self::$tenantResolver instanceof Closure ? (self::$tenantResolver)() : '');
    }
}
