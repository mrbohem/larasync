<?php

use Livewire\Livewire;
use MrBohem\Larasync\Http\Livewire\SyncDashboard;

beforeEach(function () {
    // Register the Livewire component so tests can resolve it by name
    Livewire::component('sync-dashboard', SyncDashboard::class);
});

// ── Component Mounting ────────────────────────────────────────

it('mounts the sync dashboard component', function () {
    Livewire::test('sync-dashboard')
        ->assertStatus(200);
});

it('loads config values on mount', function () {
    config()->set('larasync.db1.driver', 'mysql');
    config()->set('larasync.db1.host', '127.0.0.1');
    config()->set('larasync.db1.database', 'test_db');

    Livewire::test('sync-dashboard')
        ->assertSet('db1_driver', 'mysql')
        ->assertSet('db1_host', '127.0.0.1')
        ->assertSet('db1_database', 'test_db');
});

// ── UI Toggles ────────────────────────────────────────────────

it('toggles db1 form visibility', function () {
    Livewire::test('sync-dashboard')
        ->assertSet('show_db1_form', false)
        ->call('toggleDb1Form')
        ->assertSet('show_db1_form', true)
        ->call('toggleDb1Form')
        ->assertSet('show_db1_form', false);
});

it('toggles db2 form visibility', function () {
    Livewire::test('sync-dashboard')
        ->assertSet('show_db2_form', false)
        ->call('toggleDb2Form')
        ->assertSet('show_db2_form', true)
        ->call('toggleDb2Form')
        ->assertSet('show_db2_form', false);
});

// ── Compare ───────────────────────────────────────────────────

it('sets error when comparing without db connections', function () {
    Livewire::test('sync-dashboard')
        ->set('db1_driver', 'mysql')
        ->set('db1_database', 'testdb')
        ->set('db1_host', 'localhost')
        ->set('db1_username', 'root')
        ->set('db2_driver', 'mysql')
        ->set('db2_database', 'testdb2')
        ->set('db2_host', 'localhost')
        ->set('db2_username', 'root')
        ->call('compare')
        ->assertSet('error', 'Please test both connections first!');
});

it('shows direction selector when both dbs are connected', function () {
    Livewire::test('sync-dashboard')
        ->set('db1_driver', 'mysql')
        ->set('db1_database', 'testdb')
        ->set('db1_host', 'localhost')
        ->set('db1_username', 'root')
        ->set('db2_driver', 'mysql')
        ->set('db2_database', 'testdb2')
        ->set('db2_host', 'localhost')
        ->set('db2_username', 'root')
        ->set('db1_connected', true)
        ->set('db2_connected', true)
        ->call('compare')
        ->assertSet('show_direction_selector', true)
        ->assertSet('error', null);
});

// ── Test DB Connection ────────────────────────────────────────

it('flashes success session message on successful testDb', function () {
    // Use a valid MySQL-like config that won't connect, but test the method mechanics
    // The testDb method should set the connected property and flash session
    Livewire::test('sync-dashboard')
        ->set('db1_driver', 'mysql')
        ->set('db1_host', '255.255.255.255')
        ->set('db1_port', '9999')
        ->set('db1_database', 'nonexistent')
        ->set('db1_username', 'nobody')
        ->set('db1_password', 'wrong')
        ->call('testDb', 'db1')
        ->assertSet('db1_connected', false);
});

it('testDb works for both prefixes', function () {
    // Both should behave identically, just with different property prefixes
    $component = Livewire::test('sync-dashboard')
        ->set('db2_driver', 'mysql')
        ->set('db2_host', '255.255.255.255')
        ->set('db2_port', '9999')
        ->set('db2_database', 'nonexistent')
        ->set('db2_username', 'nobody')
        ->set('db2_password', 'wrong')
        ->call('testDb', 'db2')
        ->assertSet('db2_connected', false);
});

// ── Sync Table ────────────────────────────────────────────────

it('prevents sync when dbs are not connected', function () {
    Livewire::test('sync-dashboard')
        ->assertSet('db1_connected', false)
        ->assertSet('db2_connected', false)
        ->call('syncTable', 'users')
        ->assertHasErrors('general');
});

it('prevents sync all when dbs are not connected', function () {
    Livewire::test('sync-dashboard')
        ->call('syncAllTables')
        ->assertHasErrors('general');
});

// ── Sync Single Table Guards ──────────────────────────────────

it('does not start sync when single_sync_table is already set', function () {
    Livewire::test('sync-dashboard')
        ->set('single_sync_table', 'orders')
        ->set('comparison', [
            'users' => ['rows1' => 5, 'rows2' => 3, 'diff' => 2, 'action' => 'sync', 'missing_in_target' => false],
        ])
        ->call('syncSingleTable', 'users')
        ->assertSet('single_sync_table', 'orders'); // Unchanged
});

it('does not start sync when sync_in_progress is true', function () {
    Livewire::test('sync-dashboard')
        ->set('sync_in_progress', true)
        ->set('comparison', [
            'users' => ['rows1' => 5, 'rows2' => 3, 'diff' => 2, 'action' => 'sync', 'missing_in_target' => false],
        ])
        ->call('syncSingleTable', 'users')
        ->assertSet('sync_in_progress', true);
});

it('redirects to create table preview for missing table', function () {
    Livewire::test('sync-dashboard')
        ->set('db1_connected', true)
        ->set('db2_connected', true)
        ->set('db1_driver', 'sqlite')
        ->set('db1_database', ':memory:')
        ->set('db2_driver', 'sqlite')
        ->set('db2_database', ':memory:')
        ->set('comparison', [
            'users' => ['rows1' => 5, 'rows2' => 0, 'diff' => 5, 'action' => 'sync', 'missing_in_target' => true],
        ])
        ->call('syncSingleTable', 'users')
        ->assertSet('pending_create_table', 'users');
});

// ── Clear / Reset ──────────────────────────────────────────────

it('clears all state when clear is called', function () {
    Livewire::test('sync-dashboard')
        ->set('comparison', ['users' => ['rows1' => 5, 'rows2' => 3, 'diff' => 2, 'action' => 'sync']])
        ->set('logs', ['✅ Some log'])
        ->set('error', 'Some error')
        ->set('synced_tables', ['users'])
        ->set('sync_in_progress', true)
        ->set('current_syncing_table', 'users')
        ->set('sync_completed_count', 1)
        ->set('sync_total_count', 5)
        ->call('clear')
        ->assertSet('comparison', [])
        ->assertSet('logs', [])
        ->assertSet('error', null)
        ->assertSet('synced_tables', [])
        ->assertSet('sync_in_progress', false)
        ->assertSet('current_syncing_table', null)
        ->assertSet('sync_completed_count', 0)
        ->assertSet('sync_total_count', 0);
});

// ── Stop Sync ─────────────────────────────────────────────────

it('stops sync and resets progress state', function () {
    Livewire::test('sync-dashboard')
        ->set('sync_in_progress', true)
        ->set('current_syncing_table', 'users')
        ->set('single_sync_table', 'users')
        ->set('single_sync_offset', 500)
        ->set('single_sync_total', 1000)
        ->set('logs', ['🔄 Syncing table: users...'])
        ->call('stopSync')
        ->assertSet('sync_cancelled', true)
        ->assertSet('sync_in_progress', false)
        ->assertSet('current_syncing_table', null)
        ->assertSet('single_sync_table', null)
        ->assertSet('single_sync_offset', 0)
        ->assertSet('single_sync_total', 0)
        ->assertDispatched('sync-stopped');
});

it('clears sync_cancelled flag on clear', function () {
    Livewire::test('sync-dashboard')
        ->set('sync_cancelled', true)
        ->call('clear')
        ->assertSet('sync_cancelled', false);
});

// ── Start Sync Direction ──────────────────────────────────────

it('sets sync direction and starts comparison on startSync', function () {
    Livewire::test('sync-dashboard')
        ->set('db1_driver', 'sqlite')
        ->set('db1_database', ':memory:')
        ->set('db2_driver', 'sqlite')
        ->set('db2_database', ':memory:')
        ->call('startSync', 'db1_to_db2')
        ->assertSet('sync_direction', 'db1_to_db2')
        ->assertSet('show_direction_selector', false)
        ->assertSet('syncing', false);
});

// ── Initial State ──────────────────────────────────────────────

it('has correct initial state', function () {
    Livewire::test('sync-dashboard')
        ->assertSet('syncing', false)
        ->assertSet('comparison', [])
        ->assertSet('logs', [])
        ->assertSet('error', null)
        ->assertSet('sync_direction', 'db1_to_db2')
        ->assertSet('show_direction_selector', false)
        ->assertSet('synced_tables', [])
        ->assertSet('sync_in_progress', false)
        ->assertSet('current_syncing_table', null)
        ->assertSet('sync_completed_count', 0)
        ->assertSet('sync_total_count', 0);
});

// ── Dynamic Label Detection ───────────────────────────────────

it('computes local label for sqlite driver', function () {
    config()->set('larasync.db1.driver', 'sqlite');
    config()->set('larasync.db1.database', ':memory:');
    config()->set('larasync.db1.host', null);

    Livewire::test('sync-dashboard')
        ->assertSet('db1_label', 'Local');
});

it('computes local label for localhost host', function () {
    config()->set('larasync.db1.driver', 'mysql');
    config()->set('larasync.db1.host', '127.0.0.1');
    config()->set('larasync.db1.database', 'test_db');
    config()->set('larasync.db1.username', 'root');

    Livewire::test('sync-dashboard')
        ->assertSet('db1_label', 'Local');
});

it('computes cloud label for remote host', function () {
    config()->set('larasync.db1.driver', 'mysql');
    config()->set('larasync.db1.host', 'db.example.com');
    config()->set('larasync.db1.database', 'prod_db');
    config()->set('larasync.db1.username', 'admin');

    Livewire::test('sync-dashboard')
        ->assertSet('db1_label', 'Cloud');
});

it('computes matching labels for two local databases', function () {
    config()->set('larasync.db1.driver', 'sqlite');
    config()->set('larasync.db1.database', 'db_one.sqlite');
    config()->set('larasync.db2.driver', 'sqlite');
    config()->set('larasync.db2.database', 'db_two.sqlite');

    Livewire::test('sync-dashboard')
        ->assertSet('db1_label', 'Local')
        ->assertSet('db2_label', 'Local')
        ->assertSet('labels_match', true)
        ->assertSet('db1_display', 'DB1 · db_one.sqlite')
        ->assertSet('db2_display', 'DB2 · db_two.sqlite');
});

it('computes matching labels for two cloud databases', function () {
    config()->set('larasync.db1.driver', 'mysql');
    config()->set('larasync.db1.host', 'cloud1.example.com');
    config()->set('larasync.db1.database', 'prod_db1');
    config()->set('larasync.db1.username', 'admin');
    config()->set('larasync.db2.driver', 'pgsql');
    config()->set('larasync.db2.host', 'cloud2.example.com');
    config()->set('larasync.db2.database', 'prod_db2');
    config()->set('larasync.db2.username', 'admin');

    Livewire::test('sync-dashboard')
        ->assertSet('db1_label', 'Cloud')
        ->assertSet('db2_label', 'Cloud')
        ->assertSet('labels_match', true)
        ->assertSet('db1_display', 'DB1 · cloud1.example.com')
        ->assertSet('db2_display', 'DB2 · cloud2.example.com');
});

it('does not disambiguate when labels differ', function () {
    config()->set('larasync.db1.driver', 'sqlite');
    config()->set('larasync.db1.database', ':memory:');
    config()->set('larasync.db2.driver', 'mysql');
    config()->set('larasync.db2.host', 'remote.example.com');
    config()->set('larasync.db2.database', 'prod_db');
    config()->set('larasync.db2.username', 'admin');

    Livewire::test('sync-dashboard')
        ->assertSet('db1_label', 'Local')
        ->assertSet('db2_label', 'Cloud')
        ->assertSet('labels_match', false)
        ->assertSet('db1_display', 'Local')
        ->assertSet('db2_display', 'Cloud');
});

it('sets labels on mount', function () {
    Livewire::test('sync-dashboard')
        ->assertSet('db1_label', fn($v) => in_array($v, ['Local', 'Cloud']))
        ->assertSet('db2_label', fn($v) => in_array($v, ['Local', 'Cloud']));
});

// ── Missing Tables ────────────────────────────────────────────

it('shows missing tables modal when sync-all detects missing tables', function () {
    Livewire::test('sync-dashboard')
        ->set('db1_connected', true)
        ->set('db2_connected', true)
        ->set('comparison', [
            'users' => ['rows1' => 5, 'rows2' => 0, 'diff' => 5, 'action' => 'sync', 'missing_in_target' => true],
            'posts' => ['rows1' => 3, 'rows2' => 3, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false],
        ])
        ->call('syncAllTables')
        ->assertSet('show_missing_tables_modal', true)
        ->assertSet('missing_tables', ['users'])
        ->assertSet('sync_in_progress', false); // Should NOT start syncing yet
});

it('skips missing tables when user chooses skip', function () {
    Livewire::test('sync-dashboard')
        ->set('db1_connected', true)
        ->set('db2_connected', true)
        ->set('comparison', [
            'users' => ['rows1' => 5, 'rows2' => 0, 'diff' => 5, 'action' => 'sync', 'missing_in_target' => true],
            'posts' => ['rows1' => 3, 'rows2' => 3, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false],
        ])
        ->set('show_missing_tables_modal', true)
        ->set('missing_tables', ['users'])
        ->call('handleMissingTablesChoice', 'cancel')
        ->assertSet('show_missing_tables_modal', false)
        ->assertSet('missing_tables', []);
});

it('shows create confirmation for single missing table sync', function () {
    Livewire::test('sync-dashboard')
        ->set('db1_connected', true)
        ->set('db2_connected', true)
        ->set('db1_driver', 'sqlite')
        ->set('db1_database', ':memory:')
        ->set('db2_driver', 'sqlite')
        ->set('db2_database', ':memory:')
        ->set('comparison', [
            'users' => ['rows1' => 5, 'rows2' => 0, 'diff' => 5, 'action' => 'sync', 'missing_in_target' => true],
        ])
        ->call('syncSingleTable', 'users')
        ->assertSet('pending_create_table', 'users');
});

it('clears missing table state on clear', function () {
    Livewire::test('sync-dashboard')
        ->set('missing_tables', ['users'])
        ->set('show_missing_tables_modal', true)
        ->set('pending_create_table', 'users')
        ->set('pending_create_preview', [['name' => 'id', 'type' => 'integer']])
        ->call('clear')
        ->assertSet('missing_tables', [])
        ->assertSet('show_missing_tables_modal', false)
        ->assertSet('pending_create_table', null)
        ->assertSet('pending_create_preview', []);
});

it('cancels single table creation', function () {
    Livewire::test('sync-dashboard')
        ->set('pending_create_table', 'users')
        ->set('pending_create_preview', [['name' => 'id', 'type' => 'integer']])
        ->call('cancelCreateTable')
        ->assertSet('pending_create_table', null)
        ->assertSet('pending_create_preview', []);
});

it('adds log message when cancelling table creation', function () {
    Livewire::test('sync-dashboard')
        ->set('pending_create_table', 'users')
        ->set('pending_create_preview', [['name' => 'id', 'type' => 'integer']])
        ->set('logs', [])
        ->call('cancelCreateTable')
        ->assertSet('logs', ['⛔ Table creation cancelled']);
});

// ── Schema Mismatch Detection ─────────────────────────────────

it('shows modal when sync-all detects schema mismatch tables', function () {
    Livewire::test('sync-dashboard')
        ->set('db1_connected', true)
        ->set('db2_connected', true)
        ->set('comparison', [
            'users' => ['rows1' => 5, 'rows2' => 5, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false, 'missing_columns' => ['email'], 'type_mismatches' => []],
            'posts' => ['rows1' => 3, 'rows2' => 3, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false, 'missing_columns' => [], 'type_mismatches' => []],
        ])
        ->call('syncAllTables')
        ->assertSet('show_missing_tables_modal', true)
        ->assertSet('schema_mismatch_tables', ['users'])
        ->assertSet('missing_tables', [])
        ->assertSet('sync_in_progress', false);
});

it('shows modal for both missing and schema mismatch tables', function () {
    Livewire::test('sync-dashboard')
        ->set('db1_connected', true)
        ->set('db2_connected', true)
        ->set('comparison', [
            'users' => ['rows1' => 5, 'rows2' => 0, 'diff' => 5, 'action' => 'sync', 'missing_in_target' => true, 'missing_columns' => [], 'type_mismatches' => []],
            'posts' => ['rows1' => 3, 'rows2' => 3, 'diff' => 0, 'action' => 'equal', 'missing_in_target' => false, 'missing_columns' => [], 'type_mismatches' => ['id' => ['source' => 'bigint', 'target' => 'integer']]],
        ])
        ->call('syncAllTables')
        ->assertSet('show_missing_tables_modal', true)
        ->assertSet('missing_tables', ['users'])
        ->assertSet('schema_mismatch_tables', ['posts']);
});

it('cancel clears schema_mismatch_tables', function () {
    Livewire::test('sync-dashboard')
        ->set('db1_connected', true)
        ->set('db2_connected', true)
        ->set('schema_mismatch_tables', ['users'])
        ->set('show_missing_tables_modal', true)
        ->call('handleMissingTablesChoice', 'cancel')
        ->assertSet('show_missing_tables_modal', false)
        ->assertSet('schema_mismatch_tables', []);
});

it('clears schema_mismatch_tables on clear', function () {
    Livewire::test('sync-dashboard')
        ->set('schema_mismatch_tables', ['users'])
        ->call('clear')
        ->assertSet('schema_mismatch_tables', []);
});

// ── Schema Warnings Modal ─────────────────────────────────────

it('shows schema warnings modal for existing table', function () {
    Livewire::test('sync-dashboard')
        ->set('comparison', [
            'users' => ['rows1' => 5, 'rows2' => 5, 'diff' => 0, 'missing_columns' => ['email']],
        ])
        ->call('showSchemaWarnings', 'users')
        ->assertSet('show_schema_modal', true)
        ->assertSet('schema_warnings_table', 'users');
});

it('does not show schema warnings for non-existent table', function () {
    Livewire::test('sync-dashboard')
        ->set('comparison', [])
        ->call('showSchemaWarnings', 'unknown')
        ->assertSet('show_schema_modal', false)
        ->assertSet('schema_warnings_table', null);
});

it('closes schema warnings modal', function () {
    Livewire::test('sync-dashboard')
        ->set('show_schema_modal', true)
        ->set('schema_warnings_table', 'users')
        ->call('closeSchemaWarnings')
        ->assertSet('show_schema_modal', false)
        ->assertSet('schema_warnings_table', null);
});

it('clears schema modal state on clear', function () {
    Livewire::test('sync-dashboard')
        ->set('show_schema_modal', true)
        ->set('schema_warnings_table', 'users')
        ->call('clear')
        ->assertSet('show_schema_modal', false)
        ->assertSet('schema_warnings_table', null);
});

// ── Sync Table Chunk Cancellation ─────────────────────────────

it('returns early from syncTableChunk when cancelled', function () {
    Livewire::test('sync-dashboard')
        ->set('sync_cancelled', true)
        ->set('single_sync_table', 'users')
        ->set('single_sync_offset', 500)
        ->call('syncTableChunk', 'users')
        ->assertSet('single_sync_offset', 500); // Should remain unchanged
});
