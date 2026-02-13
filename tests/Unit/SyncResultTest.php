<?php

use MrBohem\Larasync\Support\SyncResult;

it('stores success state correctly', function () {
    $result = new SyncResult(success: true, rowCount: 42, message: 'Synced 42 rows to users');

    expect($result->success)->toBeTrue();
    expect($result->rowCount)->toBe(42);
    expect($result->message)->toBe('Synced 42 rows to users');
});

it('stores failure state correctly', function () {
    $result = new SyncResult(success: false, rowCount: 0, message: 'Sync failed for users: connection refused');

    expect($result->success)->toBeFalse();
    expect($result->rowCount)->toBe(0);
    expect($result->message)->toContain('Sync failed');
});

it('has readonly properties', function () {
    $result = new SyncResult(success: true, rowCount: 10, message: 'ok');

    $reflection = new ReflectionClass($result);

    expect($reflection->getProperty('success')->isReadOnly())->toBeTrue();
    expect($reflection->getProperty('rowCount')->isReadOnly())->toBeTrue();
    expect($reflection->getProperty('message')->isReadOnly())->toBeTrue();
});
