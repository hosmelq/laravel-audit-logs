<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Data\AuditLogChangesData;

it('omits unchanged attributes', function (): void {
    $attributes = ['empty' => '', 'enabled' => false, 'count' => 0, 'optional' => null, 'tags' => ['one', 'two']];

    expect(AuditLogChangesData::between($attributes, $attributes)->toArray())
        ->toBe(['before' => [], 'after' => []]);
});

it('preserves added removed and strictly changed values', function (array $before, array $after): void {
    $changes = AuditLogChangesData::between($before, $after);

    expect($changes->toArray())->toBe(['before' => $before, 'after' => $after]);
})->with([
    'added null' => [[], ['name' => null]],
    'removed null' => [['name' => null], []],
    'false to zero' => [['enabled' => false], ['enabled' => 0]],
    'zero to false' => [['enabled' => 0], ['enabled' => false]],
    'empty to null' => [['name' => ''], ['name' => null]],
    'numeric type change' => [['count' => 1], ['count' => '1']],
    'integer to float' => [['count' => 1], ['count' => 1.0]],
    'nested values' => [['settings' => ['enabled' => false]], ['settings' => ['enabled' => true]]],
]);

it('selects changed attributes from complete snapshots', function (): void {
    expect(AuditLogChangesData::between(
        before: ['title' => 'Draft', 'visibility' => 'private', 'removed' => 0],
        after: ['title' => 'Published', 'visibility' => 'private', 'added' => false],
    )->toArray())->toBe([
        'before' => ['title' => 'Draft', 'removed' => 0],
        'after' => ['title' => 'Published', 'added' => false],
    ]);
});

it('preserves prepared change data without comparing masked values again', function (): void {
    $changes = new AuditLogChangesData(before: ['email' => 'hidden'], after: ['email' => 'hidden']);

    expect($changes->toArray())->toBe(['before' => ['email' => 'hidden'], 'after' => ['email' => 'hidden']]);
});
