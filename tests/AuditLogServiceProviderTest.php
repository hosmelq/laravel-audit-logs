<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog as AuditLogFacade;
use HosmelQ\AuditLog\Models\AuditLog;

it('records audit logs through the facade', function (): void {
    AuditLogFacade::record(new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'request.recorded',
        source: 'tests',
    ));

    expect(AuditLog::query()->pluck('event')->all())->toBe(['request.recorded']);
});
