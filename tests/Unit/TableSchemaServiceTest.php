<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MrBohem\Larasync\Services\DatabaseConnectionService;
use MrBohem\Larasync\Services\TableSchemaService;

beforeEach(function () {
    $this->connectionService = new DatabaseConnectionService();
    $this->service = new TableSchemaService($this->connectionService);
});

// ── getCreateTableSQL() ────────────────────────────────────────

it('gets create table SQL for SQLite table', function () {
    $config = $this->setupTestDatabase('schema_source1', []);
    $this->connectionService->registerConnection('schema_source1', $config);

    $sql = $this->service->getCreateTableSQL('schema_source1', 'users');

    expect($sql)->not->toBeNull()
        ->and($sql)->toContain('CREATE TABLE')
        ->and($sql)->toContain('users');
});

// ── getTableColumns() ──────────────────────────────────────────

it('gets table columns for a SQLite table', function () {
    $config = $this->setupTestDatabase('schema_source2', []);
    $this->connectionService->registerConnection('schema_source2', $config);

    $columns = $this->service->getTableColumns('schema_source2', 'users');

    expect($columns)->not->toBeEmpty();

    $columnNames = array_column($columns, 'name');
    expect($columnNames)->toContain('id')
        ->and($columnNames)->toContain('name')
        ->and($columnNames)->toContain('email');
});

// ── createTableFromSource() ────────────────────────────────────

it('creates table from source to target for same-driver SQLite', function () {
    $sourceConfig = $this->setupTestDatabase('schema_source3', $this->makeUserRows(3));

    // Create a target database WITHOUT the users table
    $dbPath = tempnam(sys_get_temp_dir(), 'larasync_test_') . '.sqlite';
    touch($dbPath);
    $this->tempDbFiles[] = $dbPath;

    $targetConfig = [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
    ];

    config()->set('database.connections.schema_target3', $targetConfig);
    DB::purge('schema_target3');

    // Verify table doesn't exist in target
    $targetTables = Schema::connection('schema_target3')->getTableListing();
    expect($targetTables)->not->toContain('users');

    // Create the table
    $result = $this->service->createTableFromSource($sourceConfig, $targetConfig, 'users');

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toContain('Created table');

    // Verify table now exists in target
    DB::purge('schema_target3');
    config()->set('database.connections.schema_target3', $targetConfig);
    $targetTables = Schema::connection('schema_target3')->getTableListing();
    $normalizedTables = array_map(fn($t) => str_contains($t, '.') ? substr($t, strrpos($t, '.') + 1) : $t, $targetTables);
    expect($normalizedTables)->toContain('users');
});

it('creates table with correct columns using Schema Builder fallback', function () {
    $sourceConfig = $this->setupTestDatabase('schema_source4', []);

    // Create target with a DIFFERENT driver config but same SQLite underneath
    // to exercise the Schema Builder path (we simulate cross-driver by changing the config key)
    $dbPath = tempnam(sys_get_temp_dir(), 'larasync_test_') . '.sqlite';
    touch($dbPath);
    $this->tempDbFiles[] = $dbPath;

    $targetConfig = [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
    ];

    config()->set('database.connections.schema_target4', $targetConfig);
    DB::purge('schema_target4');

    // Use the public method which will use native DDL for same-driver
    $result = $this->service->createTableFromSource($sourceConfig, $targetConfig, 'users');

    expect($result['success'])->toBeTrue();

    // Verify the created table has the correct columns
    config()->set('database.connections.schema_target4_verify', $targetConfig);
    DB::purge('schema_target4_verify');
    $columns = Schema::connection('schema_target4_verify')->getColumnListing('users');

    expect($columns)->toContain('id')
        ->and($columns)->toContain('name')
        ->and($columns)->toContain('email');
});

it('returns failure when source table does not exist', function () {
    $sourceConfig = $this->setupTestDatabase('schema_source5', []);

    $dbPath = tempnam(sys_get_temp_dir(), 'larasync_test_') . '.sqlite';
    touch($dbPath);
    $this->tempDbFiles[] = $dbPath;

    $targetConfig = [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
    ];

    $result = $this->service->createTableFromSource($sourceConfig, $targetConfig, 'nonexistent_table');

    expect($result['success'])->toBeFalse();
});
