<?php

declare(strict_types=1);

use HosmelQ\AuditLog\AuditLogContext;
use HosmelQ\AuditLog\Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->in(__DIR__)
    ->beforeEach(function (): void {
        $this->freezeSecond();
    })
    ->afterEach(function (): void {
        AuditLogContext::resolveActorUsing(null);
        AuditLogContext::resolveTenantUsing(null);
    });
