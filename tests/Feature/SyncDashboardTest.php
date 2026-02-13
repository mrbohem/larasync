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

