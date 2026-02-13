<?php

use MrBohem\Larasync\Services\DatabaseConnectionService;
use MrBohem\Larasync\Services\TableComparisonService;

beforeEach(function () {
    $this->connectionService = new DatabaseConnectionService();
    $this->service = new TableComparisonService($this->connectionService);
});

// ── normalizeTableName() ───────────────────────────────────────

it('strips schema prefix from table name', function () {
    expect($this->service->normalizeTableName('main.users'))->toBe('users');
});

it('strips any database prefix from table name', function () {
    expect($this->service->normalizeTableName('mydb.posts'))->toBe('posts');
});

it('returns plain table name unchanged', function () {
    expect($this->service->normalizeTableName('users'))->toBe('users');
});

it('handles table names with multiple dots by stripping first prefix', function () {
    // "schema.db.table" → "db.table" (strips only up to first dot)
    expect($this->service->normalizeTableName('schema.db.table'))->toBe('db.table');
});

// ── compare() ──────────────────────────────────────────────────

it('identifies tables with more rows in source as sync', function () {
    // Source: 5 rows, Target: 2 rows → diff > 0 → action = "sync"
    $sourceConfig = $this->setupTestDatabase('compare_source1', $this->makeUserRows(5));
    $targetConfig = $this->setupTestDatabase('compare_target1', $this->makeUserRows(2));

    $result = $this->service->compare($sourceConfig, $targetConfig);

    expect($result)->toHaveKey('users')
        ->and($result['users']['rows1'])->toBe(5)
        ->and($result['users']['rows2'])->toBe(2)
        ->and($result['users']['diff'])->toBe(3)
        ->and($result['users']['action'])->toBe('sync');
});

it('identifies tables with fewer rows in source as update', function () {
    // Source: 2 rows, Target: 5 rows → diff < 0 → action = "update"
    $sourceConfig = $this->setupTestDatabase('compare_source2', $this->makeUserRows(2));
    $targetConfig = $this->setupTestDatabase('compare_target2', $this->makeUserRows(5));

    $result = $this->service->compare($sourceConfig, $targetConfig);

    expect($result['users']['action'])->toBe('update')
        ->and($result['users']['diff'])->toBe(-3);
});

it('identifies tables with equal rows as equal', function () {
    $sourceConfig = $this->setupTestDatabase('compare_source3', $this->makeUserRows(3));
    $targetConfig = $this->setupTestDatabase('compare_target3', $this->makeUserRows(3));

    $result = $this->service->compare($sourceConfig, $targetConfig);

    expect($result['users']['action'])->toBe('equal')
        ->and($result['users']['diff'])->toBe(0);
});

it('handles empty tables as equal with zero rows', function () {
    $sourceConfig = $this->setupTestDatabase('compare_source4', []);
    $targetConfig = $this->setupTestDatabase('compare_target4', []);

    $result = $this->service->compare($sourceConfig, $targetConfig);

    expect($result['users']['rows1'])->toBe(0)
        ->and($result['users']['rows2'])->toBe(0)
        ->and($result['users']['action'])->toBe('equal');
});

it('excludes tables listed in ignored_tables config', function () {
    config()->set('larasync.ignored_tables', ['users']);

    $sourceConfig = $this->setupTestDatabase('compare_source5', $this->makeUserRows(3));
    $targetConfig = $this->setupTestDatabase('compare_target5', $this->makeUserRows(1));

    $result = $this->service->compare($sourceConfig, $targetConfig);

    expect($result)->not->toHaveKey('users');
});
