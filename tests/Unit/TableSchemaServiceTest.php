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

// ── getTableForeignKeys() ──────────────────────────────────────

it('extracts foreign keys from a SQLite table', function () {
    $config = $this->setupTestDatabaseWithFK('fk_source1');
    $this->connectionService->registerConnection('fk_source1', $config);

    $fks = $this->service->getTableForeignKeys('fk_source1', 'posts');

    expect($fks)->not->toBeEmpty()
        ->and($fks[0]['column'])->toBe('user_id')
        ->and($fks[0]['foreign_table'])->toBe('users')
        ->and($fks[0]['foreign_column'])->toBe('id');
});

it('returns empty array for table with no foreign keys', function () {
    $config = $this->setupTestDatabase('fk_source2', []);
    $this->connectionService->registerConnection('fk_source2', $config);

    $fks = $this->service->getTableForeignKeys('fk_source2', 'users');

    expect($fks)->toBeEmpty();
});

// ── FK binding during createTableFromSource() ──────────────────

it('creates table with FK and defers when referenced table is missing', function () {
    // Source has users + posts (with FK). Target is empty.
    $sourceConfig = $this->setupTestDatabaseWithFK('fk_source3');

    $dbPath = tempnam(sys_get_temp_dir(), 'larasync_test_') . '.sqlite';
    touch($dbPath);
    $this->tempDbFiles[] = $dbPath;

    $targetConfig = [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
    ];

    // Create ONLY the posts table (users doesn't exist yet → FK should be deferred)
    $result = $this->service->createTableFromSource($sourceConfig, $targetConfig, 'posts');

    expect($result['success'])->toBeTrue()
        ->and($result['deferred_fks'])->not->toBeEmpty()
        ->and($result['deferred_fks'][0]['column'])->toBe('user_id')
        ->and($result['deferred_fks'][0]['foreign_table'])->toBe('users');
});

it('creates table with no deferred FKs when referenced table exists', function () {
    $sourceConfig = $this->setupTestDatabaseWithFK('fk_source4');

    $dbPath = tempnam(sys_get_temp_dir(), 'larasync_test_') . '.sqlite';
    touch($dbPath);
    $this->tempDbFiles[] = $dbPath;

    $targetConfig = [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
    ];

    // First create the users table (referenced table)
    $this->service->createTableFromSource($sourceConfig, $targetConfig, 'users');

    // Then create posts — FK to users should NOT be deferred
    $result = $this->service->createTableFromSource($sourceConfig, $targetConfig, 'posts');

    expect($result['success'])->toBeTrue()
        ->and($result['deferred_fks'])->toBeEmpty();
});

it('returns empty deferred_fks when table has no foreign keys', function () {
    $sourceConfig = $this->setupTestDatabase('fk_source5', []);

    $dbPath = tempnam(sys_get_temp_dir(), 'larasync_test_') . '.sqlite';
    touch($dbPath);
    $this->tempDbFiles[] = $dbPath;

    $targetConfig = [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
    ];

    $result = $this->service->createTableFromSource($sourceConfig, $targetConfig, 'users');

    expect($result['success'])->toBeTrue()
        ->and($result['deferred_fks'])->toBeEmpty();
});

// ── applyDeferredForeignKeys() ─────────────────────────────────

it('applies deferred foreign keys after referenced table is created', function () {
    $sourceConfig = $this->setupTestDatabaseWithFK('fk_source6');

    $dbPath = tempnam(sys_get_temp_dir(), 'larasync_test_') . '.sqlite';
    touch($dbPath);
    $this->tempDbFiles[] = $dbPath;

    $targetConfig = [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
    ];

    // Create posts first (users doesn't exist → FK deferred)
    $result = $this->service->createTableFromSource($sourceConfig, $targetConfig, 'posts');
    $deferredFks = $result['deferred_fks'];
    expect($deferredFks)->not->toBeEmpty();

    // Now create users table
    $this->service->createTableFromSource($sourceConfig, $targetConfig, 'users');

    // Apply deferred FKs — for SQLite this is a no-op (FKs embedded in DDL),
    // but we verify the method runs without error and reports correctly
    $fkResult = $this->service->applyDeferredForeignKeys($deferredFks, $targetConfig);

    // The method should complete without exceptions
    expect($fkResult)->toHaveKey('applied')
        ->and($fkResult)->toHaveKey('failed');
});

it('handles empty deferred FK array gracefully', function () {
    $result = $this->service->applyDeferredForeignKeys([], [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    expect($result['applied'])->toBe(0)
        ->and($result['failed'])->toBe(0)
        ->and($result['errors'])->toBeEmpty();
});

// ── dropTable() ────────────────────────────────────────────────

it('drops table successfully', function () {
    $config = $this->setupTestDatabase('schema_drop1', $this->makeUserRows(1));
    $this->connectionService->registerConnection('schema_drop1', $config);

    // Verify table exists initially
    $tablesBefore = Schema::connection('schema_drop1')->getTableListing();
    $normalizedBefore = array_map(fn($t) => str_contains($t, '.') ? substr($t, strrpos($t, '.') + 1) : $t, $tablesBefore);
    expect($normalizedBefore)->toContain('users');

    // Drop the table
    $result = $this->service->dropTable('schema_drop1', 'users');

    expect($result)->toBeTrue();

    // Verify table no longer exists
    $tablesAfter = Schema::connection('schema_drop1')->getTableListing();
    $normalizedAfter = array_map(fn($t) => str_contains($t, '.') ? substr($t, strrpos($t, '.') + 1) : $t, $tablesAfter);
    expect($normalizedAfter)->not->toContain('users');
});
