<?php

declare(strict_types=1);

use HosmelQ\AuditLog\AuditLogId;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\Facades\AuditLog;
use HosmelQ\AuditLog\Tests\TestSupport\TestEvent;
use Illuminate\Support\Str;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\Uid\Ulid;

it('fails when expected audit logs are not recorded with the same correlation id', function (): void {
    AuditLog::fake();

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'fake.first',
        source: 'tests',
    ));
    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'fake.second',
        source: 'tests',
    ));

    expect(fn () => AuditLog::assertRecordedInCorrelation('fake.first', 'fake.second'))
        ->toThrow(AssertionFailedError::class);
});

it('asserts nothing was recorded', function (): void {
    AuditLog::fake();

    AuditLog::assertNothingRecorded();
});

it('records audit logs and supports assertions', function (): void {
    AuditLog::fake();

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'fake.recorded',
        source: 'tests',
    ));

    AuditLog::assertRecorded('fake.recorded');
    AuditLog::assertRecordedTimes('fake.recorded');
    AuditLog::assertNotRecorded('fake.missing');
});

it('supports enums in fake assertions', function (): void {
    AuditLog::fake();

    AuditLog::record(new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: TestEvent::AccountUpdated->value,
        source: 'tests',
    ));

    AuditLog::assertRecordedTimes(TestEvent::AccountUpdated);
    AuditLog::assertNotRecorded(TestEvent::AccountClosed);
});

it('asserts recorded audit logs share a correlation id', function (): void {
    AuditLog::fake();

    AuditLog::record([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: TestEvent::AccountUpdated->value,
            source: 'tests',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: TestEvent::AccountClosed->value,
            source: 'tests',
        ),
    ]);

    AuditLog::assertRecordedInCorrelation(TestEvent::AccountUpdated, TestEvent::AccountClosed);
});

it('uses the registered audit log id generator for fake correlations', function (): void {
    app()->scoped(AuditLogId::class, fn (): AuditLogId => new class () extends AuditLogId {
        public function correlation(): string
        {
            return 'custom-correlation-id';
        }
    });

    AuditLog::fake();

    AuditLog::record([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.first',
            source: 'tests',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.second',
            source: 'tests',
        ),
    ]);

    expect(AuditLog::recorded()->pluck('correlationId')->all())
        ->toBe(['custom-correlation-id', 'custom-correlation-id']);
});

it('records snapshots with ids and correlation ids when recording many logs', function (): void {
    AuditLog::fake();
    Str::createUlidsUsingSequence([new Ulid('01HX0000000000000000000000')]);

    $first = new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'fake.first',
        source: 'tests',
        id: 'log-1',
    );
    $second = new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'fake.second',
        source: 'tests',
        id: 'log-2',
    );

    AuditLog::record([$first, $second]);

    $recorded = AuditLog::recorded();

    expect($first->correlationId)
        ->toBeNull()
        ->and($second->correlationId)->toBeNull()
        ->and($recorded[0]->id)->toBe($first->id)
        ->and($recorded[1]->id)->toBe($second->id)
        ->and($recorded[0]->correlationId)->toBe('cor_01HX0000000000000000000000')->toBe($recorded[1]->correlationId);
});

it('preserves explicit fake correlation ids while filling missing correlations', function (): void {
    AuditLog::fake();
    Str::createUlidsUsingSequence([new Ulid('01HX0000000000000000000000')]);

    AuditLog::record([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.first',
            source: 'tests',
            correlationId: 'explicit-correlation',
            id: 'log-1',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.second',
            source: 'tests',
            id: 'log-2',
        ),
    ]);

    expect(AuditLog::recorded()->pluck('correlationId')->all())
        ->toBe(['explicit-correlation', 'cor_01HX0000000000000000000000']);
});

it('preserves explicit fake correlation ids without generating one', function (): void {
    app()->instance(AuditLogId::class, new class () extends AuditLogId {
        public function correlation(): string
        {
            throw new RuntimeException('Correlation id should not be generated.');
        }
    });

    AuditLog::fake();

    AuditLog::record([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.first',
            source: 'tests',
            correlationId: 'correlation-1',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.second',
            source: 'tests',
            correlationId: 'correlation-2',
        ),
    ]);

    expect(AuditLog::recorded()->pluck('correlationId')->all())
        ->toBe(['correlation-1', 'correlation-2']);
});

it('records fake correlations from iterables', function (): void {
    AuditLog::fake();
    Str::createUlidsUsingSequence([new Ulid('01HX0000000000000000000000')]);

    AuditLog::record((function (): Generator {
        yield new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.first',
            source: 'tests',
            id: 'log-1',
        );
        yield new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.second',
            source: 'tests',
            id: 'log-2',
        );
    })());

    expect(AuditLog::recorded()->pluck('correlationId')->all())
        ->toBe(['cor_01HX0000000000000000000000', 'cor_01HX0000000000000000000000']);
});

it('does not assign a fake correlation id when recording one log from an iterable', function (): void {
    AuditLog::fake();

    AuditLog::record((function (): Generator {
        yield new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.first',
            source: 'tests',
        );
    })());

    expect(AuditLog::recorded()->pluck('correlationId')->all())
        ->toBe([null]);
});

it('correlates separate fake records inside a scope', function (): void {
    AuditLog::fake();

    AuditLog::correlate(function (): void {
        AuditLog::record(new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.first',
            source: 'tests',
        ));
        AuditLog::record(new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'fake.second',
            source: 'tests',
        ));
    }, 'correlation-1');

    expect(AuditLog::recorded()->pluck('correlationId')->all())
        ->toBe(['correlation-1', 'correlation-1']);
});
