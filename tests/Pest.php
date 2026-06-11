<?php

declare(strict_types=1);

use function Pest\Laravel\freezeSecond;

use HosmelQ\AuditLog\Tests\TestCase;

uses(TestCase::class)->in(__DIR__)
    ->beforeEach(function (): void {
        freezeSecond();
    });
