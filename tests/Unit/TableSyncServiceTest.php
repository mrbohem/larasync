<?php

use Illuminate\Support\Facades\DB;
use MrBohem\Larasync\Services\DatabaseConnectionService;
use MrBohem\Larasync\Services\TableComparisonService;
use MrBohem\Larasync\Services\TableSyncService;

beforeEach(function () {
    $this->connectionService = new DatabaseConnectionService();
    $this->comparisonService = new TableComparisonService($this->connectionService);
    $this->service = new TableSyncService($this->connectionService, $this->comparisonService);
});

// ── syncTable() ────────────────────────────────────────────────

it('copies all rows from source to target table', function () {
    $sourceRows = $this->makeUserRows(5);
    $sourceConfig = $this->setupTestDatabase('sync_source1', $sourceRows);
    $targetConfig = $this->setupTestDatabase('sync_target1', []);

    $result = $this->service->syncTable('users', $sourceConfig, $targetConfig);

    expect($result->success)->toBeTrue()
        ->and($result->rowCount)->toBe(5)
        ->and($result->message)->toContain('Synced 5 rows');

    // Verify target actually has the synced data
    $targetCount = DB::connection('sync_target1')->table('users')->count();
    expect($targetCount)->toBe(5);
});

it('truncates target before syncing', function () {
    $sourceConfig = $this->setupTestDatabase('sync_source2', $this->makeUserRows(3));
    $targetConfig = $this->setupTestDatabase('sync_target2', $this->makeUserRows(10));

    $result = $this->service->syncTable('users', $sourceConfig, $targetConfig);

    expect($result->success)->toBeTrue()
        ->and($result->rowCount)->toBe(3);

    // Target should now have exactly 3 rows, not 13
    $targetCount = DB::connection('sync_target2')->table('users')->count();
    expect($targetCount)->toBe(3);
});

it('handles empty source table gracefully', function () {
    $sourceConfig = $this->setupTestDatabase('sync_source3', []);
    $targetConfig = $this->setupTestDatabase('sync_target3', $this->makeUserRows(5));

    $result = $this->service->syncTable('users', $sourceConfig, $targetConfig);

    expect($result->success)->toBeTrue()
        ->and($result->rowCount)->toBe(0)
        ->and($result->message)->toContain('Synced 0 rows');

    // Target should be empty now
    $targetCount = DB::connection('sync_target3')->table('users')->count();
    expect($targetCount)->toBe(0);
});

it('handles large datasets with chunking', function () {
    // Create more than 500 rows to exercise the chunking logic
    $rows = [];
    for ($i = 1; $i <= 550; $i++) {
        $rows[] = [
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    $sourceConfig = $this->setupTestDatabase('sync_source4', $rows);
    $targetConfig = $this->setupTestDatabase('sync_target4', []);

    $result = $this->service->syncTable('users', $sourceConfig, $targetConfig);

    expect($result->success)->toBeTrue()
        ->and($result->rowCount)->toBe(550);

    $targetCount = DB::connection('sync_target4')->table('users')->count();
    expect($targetCount)->toBe(550);
});

it('returns failure result when source table does not exist', function () {
    $sourceConfig = $this->setupTestDatabase('sync_source5', []);
    $targetConfig = $this->setupTestDatabase('sync_target5', []);

    $result = $this->service->syncTable('nonexistent_table', $sourceConfig, $targetConfig);

    expect($result->success)->toBeFalse()
        ->and($result->rowCount)->toBe(0)
        ->and($result->message)->toContain('Sync failed');
});

it('returns failure result when connection config is invalid', function () {
    $invalidConfig = [
        'driver' => 'mysql',
        'host' => '255.255.255.255',
        'port' => '9999',
        'database' => 'nonexistent',
        'username' => 'nobody',
        'password' => 'wrong',
    ];
    $targetConfig = $this->setupTestDatabase('sync_target6', []);

    $result = $this->service->syncTable('users', $invalidConfig, $targetConfig);

    expect($result->success)->toBeFalse()
        ->and($result->rowCount)->toBe(0)
        ->and($result->message)->toContain('Sync failed');
});

it('preserves data integrity after sync', function () {
    $sourceRows = [
        ['name' => 'Alice', 'email' => 'alice@test.com', 'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString()],
        ['name' => 'Bob', 'email' => 'bob@test.com', 'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString()],
    ];
    $sourceConfig = $this->setupTestDatabase('sync_source7', $sourceRows);
    $targetConfig = $this->setupTestDatabase('sync_target7', []);

    $this->service->syncTable('users', $sourceConfig, $targetConfig);

    $targetData = DB::connection('sync_target7')->table('users')->orderBy('id')->get();

    expect($targetData)->toHaveCount(2)
        ->and($targetData[0]->name)->toBe('Alice')
        ->and($targetData[0]->email)->toBe('alice@test.com')
        ->and($targetData[1]->name)->toBe('Bob')
        ->and($targetData[1]->email)->toBe('bob@test.com');
});
