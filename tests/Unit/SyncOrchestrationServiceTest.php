<?php

use MrBohem\Larasync\Services\SyncOrchestrationService;

// ── detectTableIssues() ────────────────────────────────────────

it('detects missing tables from comparison data', function () {
    $service = app(SyncOrchestrationService::class);

    $comparison = [
        'users' => ['rows1' => 5, 'rows2' => 0, 'diff' => 5, 'action' => 'sync', 'missing_in_target' => true, 'missing_columns' => [], 'type_mismatches' => []],
        'posts' => ['rows1' => 3, 'rows2' => 3, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false, 'missing_columns' => [], 'type_mismatches' => []],
    ];

    $result = $service->detectTableIssues($comparison);

    expect($result['missing_tables'])->toBe(['users'])
        ->and($result['schema_mismatch_tables'])->toBe([]);
});

it('detects schema mismatch tables from comparison data', function () {
    $service = app(SyncOrchestrationService::class);

    $comparison = [
        'users' => ['rows1' => 5, 'rows2' => 5, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false, 'missing_columns' => ['email'], 'type_mismatches' => []],
        'posts' => ['rows1' => 3, 'rows2' => 3, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false, 'missing_columns' => [], 'type_mismatches' => []],
    ];

    $result = $service->detectTableIssues($comparison);

    expect($result['missing_tables'])->toBe([])
        ->and($result['schema_mismatch_tables'])->toBe(['users']);
});

it('detects both missing and schema mismatch tables', function () {
    $service = app(SyncOrchestrationService::class);

    $comparison = [
        'users' => ['rows1' => 5, 'rows2' => 0, 'diff' => 5, 'action' => 'sync', 'missing_in_target' => true, 'missing_columns' => [], 'type_mismatches' => []],
        'posts' => ['rows1' => 3, 'rows2' => 3, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false, 'missing_columns' => [], 'type_mismatches' => ['id' => ['source' => 'bigint', 'target' => 'integer']]],
        'comments' => ['rows1' => 2, 'rows2' => 2, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false, 'missing_columns' => [], 'type_mismatches' => []],
    ];

    $result = $service->detectTableIssues($comparison);

    expect($result['missing_tables'])->toBe(['users'])
        ->and($result['schema_mismatch_tables'])->toBe(['posts']);
});

it('returns empty arrays when no issues detected', function () {
    $service = app(SyncOrchestrationService::class);

    $comparison = [
        'users' => ['rows1' => 5, 'rows2' => 5, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false, 'missing_columns' => [], 'type_mismatches' => []],
    ];

    $result = $service->detectTableIssues($comparison);

    expect($result['missing_tables'])->toBe([])
        ->and($result['schema_mismatch_tables'])->toBe([]);
});

it('handles empty comparison data', function () {
    $service = app(SyncOrchestrationService::class);

    $result = $service->detectTableIssues([]);

    expect($result['missing_tables'])->toBe([])
        ->and($result['schema_mismatch_tables'])->toBe([]);
});

it('classifies missing_in_target over schema mismatch', function () {
    $service = app(SyncOrchestrationService::class);

    // A table that is missing AND has schema issues should only appear in missing_tables
    $comparison = [
        'users' => ['rows1' => 5, 'rows2' => 0, 'diff' => 5, 'action' => 'sync', 'missing_in_target' => true, 'missing_columns' => ['email'], 'type_mismatches' => ['id' => []]],
    ];

    $result = $service->detectTableIssues($comparison);

    expect($result['missing_tables'])->toBe(['users'])
        ->and($result['schema_mismatch_tables'])->toBe([]);
});
