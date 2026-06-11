<?php

declare(strict_types=1);

use HosmelQ\AuditLog\AuditLogId;
use HosmelQ\AuditLog\Contracts\AuditLogManager;
use HosmelQ\AuditLog\Data\AuditLogActorData;
use HosmelQ\AuditLog\Data\AuditLogData;
use HosmelQ\AuditLog\DatabaseAuditLogManager;
use HosmelQ\AuditLog\Models\AuditLog;
use HosmelQ\AuditLog\Tests\TestSupport\CustomAuditLog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

it('throws database errors when record fails', function (): void {
    Config::set('audit-log.storage.table', 'missing_audit_logs');

    expect(fn () => resolve(AuditLogManager::class)->record(new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
    )))
        ->toThrow(QueryException::class);
});

it('rolls back record when one insert fails in many logs', function (): void {
    expect(fn () => resolve(AuditLogManager::class)->record([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-1',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-1',
        ),
    ]))
        ->toThrow(QueryException::class)
        ->and(AuditLog::query()->count())->toBe(0);
});

it('rejects invalid custom audit log models', function (): void {
    expect(fn () => DatabaseAuditLogManager::useModel(stdClass::class))
        ->toThrow(InvalidArgumentException::class);
});

it('cleans up correlation scopes when callbacks fail', function (): void {
    expect(fn () => resolve(AuditLogManager::class)->correlate(function (): void {
        resolve(AuditLogManager::class)->record(new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-1',
        ));

        throw new RuntimeException('Scope failed.');
    }, 'correlation-1'))->toThrow(RuntimeException::class);

    resolve(AuditLogManager::class)->record(new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.closed',
        source: 'tests',
        id: 'log-2',
    ));

    expect(AuditLog::query()->orderBy('id')->pluck('correlation_id')->all())
        ->toBe(['correlation-1', null]);
});

it('uses custom audit log models', function (): void {
    DatabaseAuditLogManager::useModel(CustomAuditLog::class);

    expect(DatabaseAuditLogManager::model())->toBe(CustomAuditLog::class);
});

it('records logs immediately without adding a correlation id', function (): void {
    $log = new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
    );

    resolve(AuditLogManager::class)->record($log);

    $row = AuditLog::query()->firstOrFail();

    expect($row)
        ->id->toBe($log->id)
        ->correlation_id->toBeNull();
});

it('records missing request metadata from the current request', function (): void {
    Config::set('audit-log.request.capture_in_console', true);

    app()->instance('request', Request::create('/', 'GET', server: [
        'HTTP_USER_AGENT' => 'Browser',
        'REMOTE_ADDR' => '203.0.113.10',
    ]));

    resolve(AuditLogManager::class)->record(new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
    ));

    $log = AuditLog::query()->firstOrFail();

    expect($log)
        ->remote_ip->toBe('203.0.113.10')
        ->user_agent->toBe('Browser');
});

it('does not assign a correlation id when recording one log in an array', function (): void {
    resolve(AuditLogManager::class)->record([new AuditLogData(
        actor: new AuditLogActorData(),
        bucket: 'security',
        event: 'account.updated',
        source: 'tests',
        id: 'log-1',
    )]);

    expect(AuditLog::query()->value('correlation_id'))->toBeNull();
});

it('does not assign a correlation id when recording one log from an iterable', function (): void {
    resolve(AuditLogManager::class)->record((function (): Generator {
        yield new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-1',
        );
    })());

    expect(AuditLog::query()->value('correlation_id'))->toBeNull();
});

it('records one correlation id for every record call with multiple logs', function (): void {
    Str::createUlidsUsingSequence([new Ulid('01HX0000000000000000000000')]);

    resolve(AuditLogManager::class)->record([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-1',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-2',
        ),
    ]);

    expect(AuditLog::query()->distinct()->pluck('correlation_id')->all())
        ->toBe(['cor_01HX0000000000000000000000']);
});

it('preserves explicit ids and correlation ids', function (): void {
    app()->instance(AuditLogId::class, new class () extends AuditLogId {
        public function correlation(): string
        {
            throw new RuntimeException('Correlation id should not be generated.');
        }
    });

    resolve(AuditLogManager::class)->record([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            correlationId: 'correlation-1',
            id: 'log-1',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            correlationId: 'correlation-1',
            id: 'log-2',
        ),
    ]);

    expect(AuditLog::query()->orderBy('id')->pluck('id')->all())
        ->toBe(['log-1', 'log-2'])
        ->and(AuditLog::query()->distinct()->pluck('correlation_id')->all())->toBe(['correlation-1']);
});

it('preserves explicit correlation ids while filling missing correlations', function (): void {
    Str::createUlidsUsingSequence([new Ulid('01HX0000000000000000000000')]);

    resolve(AuditLogManager::class)->record([
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            correlationId: 'explicit-correlation',
            id: 'log-1',
        ),
        new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.closed',
            source: 'tests',
            id: 'log-2',
        ),
    ]);

    expect(AuditLog::query()->orderBy('id')->pluck('correlation_id')->all())
        ->toBe(['explicit-correlation', 'cor_01HX0000000000000000000000']);
});

it('correlates separate records inside a scope', function (): void {
    resolve(AuditLogManager::class)->correlate(function (): void {
        resolve(AuditLogManager::class)->record(new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-1',
        ));
        resolve(AuditLogManager::class)->record(new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.closed',
            source: 'tests',
            id: 'log-2',
        ));
    }, 'correlation-1');

    expect(AuditLog::query()->orderBy('id')->pluck('correlation_id')->all())
        ->toBe(['correlation-1', 'correlation-1']);
});

it('restores parent correlation scopes after nested scopes', function (): void {
    resolve(AuditLogManager::class)->correlate(function (): void {
        resolve(AuditLogManager::class)->record(new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-1',
        ));

        resolve(AuditLogManager::class)->correlate(function (): void {
            resolve(AuditLogManager::class)->record(new AuditLogData(
                actor: new AuditLogActorData(),
                bucket: 'security',
                event: 'account.closed',
                source: 'tests',
                id: 'log-2',
            ));
        }, 'inner-correlation');

        resolve(AuditLogManager::class)->record(new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.reopened',
            source: 'tests',
            id: 'log-3',
        ));
    }, 'outer-correlation');

    expect(AuditLog::query()->orderBy('id')->pluck('correlation_id')->all())
        ->toBe(['outer-correlation', 'inner-correlation', 'outer-correlation']);
});

it('ignores blank correlation ids for nested scopes', function (): void {
    resolve(AuditLogManager::class)->correlate(function (): void {
        resolve(AuditLogManager::class)->correlate(function (): void {
            resolve(AuditLogManager::class)->record(new AuditLogData(
                actor: new AuditLogActorData(),
                bucket: 'security',
                event: 'account.closed',
                source: 'tests',
            ));
        }, '   ');
    }, 'outer-correlation');

    expect(AuditLog::query()->value('correlation_id'))->toBe('outer-correlation');
});

it('records many logs from an iterable', function (): void {
    Str::createUlidsUsingSequence([new Ulid('01HX0000000000000000000000')]);

    $logs = (function (): Generator {
        yield new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-1',
        );
        yield new AuditLogData(
            actor: new AuditLogActorData(),
            bucket: 'security',
            event: 'account.updated',
            source: 'tests',
            id: 'log-2',
        );
    })();

    resolve(AuditLogManager::class)->record($logs);

    expect(AuditLog::query()->orderBy('id')->pluck('correlation_id')->all())
        ->toBe(['cor_01HX0000000000000000000000', 'cor_01HX0000000000000000000000']);
});
