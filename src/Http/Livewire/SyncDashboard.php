<?php

namespace MrBohem\Larasync\Http\Livewire;

use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MrBohem\Larasync\Services\DatabaseConnectionService;
use MrBohem\Larasync\Services\TableComparisonService;
use MrBohem\Larasync\Services\TableSyncService;
use MrBohem\Larasync\Services\TableDependencyService;

class SyncDashboard extends Component
{
    // ── DB Status ──────────────────────────────────────────────────
    public $db1_configured = false;
    public $db1_connected = false;
    public $db2_configured = false;
    public $db2_connected = false;

    // ── DB Form Fields ─────────────────────────────────────────────
    public $db1_driver;
    public $db1_host;
    public $db1_port;
    public $db1_database;
    public $db1_username;
    public $db1_password;
    public $db1_schema;
    public $db2_driver;
    public $db2_host;
    public $db2_port;
    public $db2_database;
    public $db2_username;
    public $db2_password;
    public $db2_schema;

    // ── UI State ───────────────────────────────────────────────────
    public $show_db1_form = false;
    public $show_db2_form = false;
    public $comparison = [];
    public $syncing = false;
    public $logs = [];
    public $error;

    public $sync_direction = 'db1_to_db2';
    public $show_direction_selector = false;

    // ── Dynamic Labels ────────────────────────────────────────────
    public $db1_label = 'DB1';
    public $db2_label = 'DB2';
    public $labels_match = false;
    public $db1_display = 'DB1';
    public $db2_display = 'DB2';
    public $synced_tables = [];

    // ── Sync-All Progress ──────────────────────────────────────────
    public $sync_in_progress = false;
    public $current_syncing_table = null;
    public $tables_to_sync = [];
    public $sync_completed_count = 0;
    public $sync_total_count = 0;

    // ── Single-Table Chunked Sync Progress ─────────────────────────
    public $single_sync_table = null;
    public $single_sync_offset = 0;
    public $single_sync_total = 0;

    // ── Stop / Cancel ──────────────────────────────────────────────
    public $sync_cancelled = false;

    // ── Services ───────────────────────────────────────────────────
    private DatabaseConnectionService $connectionService;
    private TableComparisonService $comparisonService;
    private TableSyncService $syncService;
    private TableDependencyService $dependencyService;

    public function boot()
    {
        $this->connectionService = new DatabaseConnectionService();
        $this->comparisonService = new TableComparisonService($this->connectionService);
        $this->syncService = new TableSyncService($this->connectionService, $this->comparisonService);
        $this->dependencyService = new TableDependencyService($this->connectionService);
    }

    public function rules()
    {
        return [
            'db1_driver' => ['required', Rule::in(['sqlite', 'mysql', 'pgsql'])],
            'db1_database' => 'required',
            'db2_driver' => ['required', Rule::in(['sqlite', 'mysql', 'pgsql'])],
            'db2_database' => 'required',
            'db1_host' => ['required_unless:db1_driver,sqlite'],
            'db1_port' => 'nullable|numeric',
            'db1_username' => ['required_unless:db1_driver,sqlite'],
            'db2_host' => ['required_unless:db2_driver,sqlite'],
            'db2_port' => 'nullable|numeric',
            'db2_username' => ['required_unless:db2_driver,sqlite'],
        ];
    }

    // ────────────────────────────────────────────────────────────────
    //  Lifecycle
    // ────────────────────────────────────────────────────────────────

    public function mount()
    {
        $this->loadConfigValues();
        $this->checkDbStatus();
        $this->updateLabels();
    }

    private function loadConfigValues()
    {
        foreach (['db1', 'db2'] as $db) {
            foreach (['driver', 'host', 'port', 'database', 'username', 'password', 'schema'] as $field) {
                $this->{$db . '_' . $field} = config("larasync.{$db}.{$field}");
            }
        }
        $this->updateLabels();
    }

    // ────────────────────────────────────────────────────────────────
    //  Connection Actions
    // ────────────────────────────────────────────────────────────────

    public function checkDbStatus()
    {
        $this->db1_configured = $this->connectionService->isConfigured('db1');
        $this->db1_connected = $this->db1_configured && $this->testDbConnection('db1');

        $this->db2_configured = $this->connectionService->isConfigured('db2');
        $this->db2_connected = $this->db2_configured && $this->testDbConnection('db2');
    }

    public function testDb1()
    {
        $this->db1_connected = $this->testDbConnection('db1');
        session()->flash(
            $this->db1_connected ? 'success' : 'error',
            $this->db1_connected ? '✅ DB1 Connected!' : '❌ DB1 Connection failed!'
        );
    }

    public function testDb2()
    {
        $this->db2_connected = $this->testDbConnection('db2');
        session()->flash(
            $this->db2_connected ? 'success' : 'error',
            $this->db2_connected ? '✅ DB2 Connected!' : '❌ DB2 Connection failed!'
        );
    }

    /**
     * Build config from current form properties and test the connection.
     */
    private function testDbConnection(string $prefix): bool
    {
        $config = $this->buildConfigFromProperties($prefix);
        if (!$config) {
            return false;
        }

        return $this->connectionService->testConnection($config, $prefix);
    }

    // ────────────────────────────────────────────────────────────────
    //  UI Toggles
    // ────────────────────────────────────────────────────────────────

    public function toggleDb1Form()
    {
        $this->show_db1_form = !$this->show_db1_form;
    }

    public function toggleDb2Form()
    {
        $this->show_db2_form = !$this->show_db2_form;
    }

    // ────────────────────────────────────────────────────────────────
    //  Compare
    // ────────────────────────────────────────────────────────────────

    public function compare()
    {
        $this->resetErrorBag();
        $this->validate();

        if (!$this->db1_connected || !$this->db2_connected) {
            $this->error = 'Please test both connections first!';
            return;
        }

        $this->show_direction_selector = true;
    }

    public function startSync($direction)
    {
        $this->sync_direction = $direction;
        $this->show_direction_selector = false;
        $this->syncing = true;
        $this->comparison = [];
        $this->logs = [];
        $this->synced_tables = [];

        try {
            $sourceConfig = $this->buildConfigFromProperties($direction === 'db1_to_db2' ? 'db1' : 'db2');
            $targetConfig = $this->buildConfigFromProperties($direction === 'db1_to_db2' ? 'db2' : 'db1');

            $this->comparison = $this->comparisonService->compare($sourceConfig, $targetConfig);
            $this->logs[] = "✅ Comparison complete: {$direction}";
        } catch (\Exception $e) {
            $this->error = 'Comparison failed: ' . $e->getMessage();
        }

        $this->syncing = false;
    }

    // ────────────────────────────────────────────────────────────────
    //  Sync
    // ────────────────────────────────────────────────────────────────

    /**
     * Increase PHP execution time for sync operations.
     * Prevents "Maximum execution time exceeded" errors on large tables.
     */
    private function increaseExecutionTime($seconds = 300)
    {
        if (function_exists('set_time_limit')) {
            set_time_limit($seconds);
        }
    }

    public function syncTable($tableName)
    {
        if (!$this->db1_connected || !$this->db2_connected) {
            $this->addError('general', 'Please test both connections first!');
            return;
        }

        $this->increaseExecutionTime(300); // 5 minutes per table
        $this->syncing = true;
        $this->logs[] = "🔄 Syncing table: {$tableName}...";

        $sourceConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db1' : 'db2');
        $targetConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db2' : 'db1');

        $result = $this->syncService->syncTable($tableName, $sourceConfig, $targetConfig);

        if ($result->success) {
            $this->logs[] = "✅ {$result->message}";
            $this->synced_tables[] = $tableName;
        } else {
            $this->logs[] = "❌ {$result->message}";
        }

        // Re-run comparison to show updated status
        $this->comparison = $this->comparisonService->compare($sourceConfig, $targetConfig);
        $this->fixComparisonRowCount($tableName, $sourceConfig, $targetConfig);
        $this->syncing = false;
    }

    public function syncSingleTable($tableName)
    {
        if ($this->single_sync_table || $this->sync_in_progress) {
            return; // Already syncing
        }

        $this->sync_cancelled = false;

        $sourceRows = $this->comparison[$tableName]['rows1'] ?? 0;

        if ($sourceRows <= 20000) {
            // Small table - use existing single-request sync
            $this->syncTable($tableName);
            return;
        }

        // Large table - start chunked sync with progress
        $this->increaseExecutionTime(300);
        $this->single_sync_table = $tableName;
        $this->single_sync_offset = 0;
        $this->single_sync_total = $sourceRows;
        $this->logs[] = "🔄 Syncing table: {$tableName} ({$sourceRows} rows, chunked)...";

        $this->dispatch('start-single-table-sync', tableName: $tableName);
    }

    public function syncTableChunk($tableName)
    {
        if ($this->sync_cancelled) {
            return;
        }

        $this->increaseExecutionTime(300);

        $sourceConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db1' : 'db2');
        $targetConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db2' : 'db1');

        $result = $this->syncService->syncTableChunk(
            $tableName,
            $sourceConfig,
            $targetConfig,
            $this->single_sync_offset
        );

        if (!$result['success']) {
            $this->logs[] = "❌ {$result['message']}";
            $this->single_sync_table = null;
            $this->single_sync_offset = 0;
            $this->single_sync_total = 0;

            // If in sync-all mode, advance to next table despite error
            if ($this->sync_in_progress) {
                $this->completeCurrentTableSync($tableName);
            }
            return;
        }

        $this->single_sync_offset = $result['offset'];

        if ($result['done']) {
            $this->logs[] = "✅ Synced {$this->single_sync_offset} rows to {$tableName}";
            $this->synced_tables[] = $tableName;

            // Re-run comparison to show updated status
            $this->comparison = $this->comparisonService->compare($sourceConfig, $targetConfig);
            $this->fixComparisonRowCount($tableName, $sourceConfig, $targetConfig);

            $this->single_sync_table = null;
            $this->single_sync_offset = 0;
            $this->single_sync_total = 0;

            // If in sync-all mode, advance to next table
            if ($this->sync_in_progress) {
                $this->completeCurrentTableSync($tableName);
            }
        } else {
            $this->dispatch('continue-single-table-sync', tableName: $tableName);
        }
    }

    public function syncAllTables()
    {
        if (!$this->db1_connected || !$this->db2_connected) {
            $this->addError('general', 'Please test both connections first!');
            return;
        }

        $this->sync_cancelled = false;
        $this->increaseExecutionTime(600); // 10 minutes for full sync
        $tables = array_keys($this->comparison);

        // Get the target config to analyze dependencies
        $targetConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db2' : 'db1');

        // Reorder tables based on foreign key dependencies
        try {
            $orderedTables = $this->dependencyService->getSyncOrder($targetConfig, $tables);
            
            if ($orderedTables !== $tables) {
                $this->logs[] = "📋 Tables reordered by dependencies:";
                $this->logs[] = "   Sync order: " . implode(' → ', $orderedTables);
                $tables = $orderedTables;
            } else {
                $this->logs[] = "📋 No dependencies detected, syncing in original order";
            }
        } catch (\Exception $e) {
            $this->logs[] = "⚠️ Could not analyze dependencies, syncing in original order";
            Log::warning("Dependency analysis failed: " . $e->getMessage());
        }

        $this->sync_in_progress = true;
        $this->tables_to_sync = $tables;
        $this->sync_completed_count = 0;
        $this->sync_total_count = count($tables);
        $this->current_syncing_table = count($tables) > 0 ? $tables[0] : null;
        $this->synced_tables = [];
        $this->logs[] = "🚀 Starting sync for all " . count($tables) . " tables...";

        $this->dispatch('start-sequential-sync', tables: $tables);
    }

    public function syncNextTable($tableName)
    {
        if ($this->sync_cancelled) {
            return;
        }

        $this->current_syncing_table = $tableName;

        $sourceRows = $this->comparison[$tableName]['rows1'] ?? 0;

        if ($sourceRows > 20000) {
            // Large table - use chunked sync with progress
            $this->increaseExecutionTime(300);
            $this->single_sync_table = $tableName;
            $this->single_sync_offset = 0;
            $this->single_sync_total = $sourceRows;
            $this->logs[] = "🔄 Syncing table: {$tableName} ({$sourceRows} rows, chunked)...";
            $this->dispatch('start-single-table-sync', tableName: $tableName);
        } else {
            // Small table - sync in one request
            $this->syncTable($tableName);
            $this->completeCurrentTableSync($tableName);
        }
    }

    /**
     * Advance sync-all progress after a table finishes syncing.
     */
    private function completeCurrentTableSync(string $tableName): void
    {
        $this->sync_completed_count++;

        if ($this->sync_completed_count >= $this->sync_total_count) {
            $this->current_syncing_table = null;
            $this->sync_in_progress = false;
            
            $this->logs[] = "✅ All {$this->sync_total_count} tables synced successfully!";
            $this->dispatch('all-tables-synced');
        } else {
            $nextIndex = array_search($tableName, $this->tables_to_sync);
            if ($nextIndex !== false && isset($this->tables_to_sync[$nextIndex + 1])) {
                $this->current_syncing_table = $this->tables_to_sync[$nextIndex + 1];
            } else {
                $this->current_syncing_table = null;
            }
            $this->dispatch('table-sync-complete', completed: $tableName);
        }
    }

    // ────────────────────────────────────────────────────────────────
    //  Stop / Cancel Sync
    // ────────────────────────────────────────────────────────────────

    public function stopSync()
    {
        $this->sync_cancelled = true;
        $this->sync_in_progress = false;
        $this->current_syncing_table = null;
        $this->single_sync_table = null;
        $this->single_sync_offset = 0;
        $this->single_sync_total = 0;
        $this->logs[] = '⛔ Sync stopped by user';
        $this->dispatch('sync-stopped');

        // Re-run comparison to show actual current row counts
        try {
            $sourceConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db1' : 'db2');
            $targetConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db2' : 'db1');
            $this->comparison = $this->comparisonService->compare($sourceConfig, $targetConfig);

            // Fix all tables with exact COUNT(*) — InnoDB estimates are stale after truncate+insert
            foreach (array_keys($this->comparison) as $table) {
                $this->fixComparisonRowCount($table, $sourceConfig, $targetConfig);
            }

            $this->logs[] = '📊 Row counts refreshed';
        } catch (\Exception $e) {
            $this->logs[] = '⚠️ Could not refresh row counts: ' . $e->getMessage();
        }
    }

    // ────────────────────────────────────────────────────────────────
    //  Clear / Reset
    // ────────────────────────────────────────────────────────────────

    public function clear()
    {
        $this->comparison = [];
        $this->logs = [];
        $this->error = null;
        $this->synced_tables = [];
        $this->sync_in_progress = false;
        $this->current_syncing_table = null;
        $this->tables_to_sync = [];
        $this->sync_completed_count = 0;
        $this->sync_total_count = 0;
        $this->single_sync_table = null;
        $this->single_sync_offset = 0;
        $this->single_sync_total = 0;
        $this->sync_cancelled = false;
        $this->checkDbStatus();
        $this->updateLabels();
    }

    // ────────────────────────────────────────────────────────────────
    //  Render
    // ────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('larasync::livewire.sync-dashboard')
            ->layout('larasync::layouts.app');
    }

    // ────────────────────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────────────────────

    /**
     * Fix comparison row counts for a specific table using exact COUNT(*).
     * MySQL's information_schema.TABLES.TABLE_ROWS returns approximate InnoDB estimates
     * which can be stale after bulk inserts (e.g. 40,000 instead of 40,007).
     */
    private function fixComparisonRowCount(string $tableName, array $sourceConfig, array $targetConfig): void
    {
        if (!isset($this->comparison[$tableName])) {
            return;
        }

        try {
            $this->connectionService->registerConnection('sync_source', $sourceConfig);
            $this->connectionService->registerConnection('sync_target', $targetConfig);

            $sourceCount = (int) DB::connection('sync_source')->table($tableName)->count();
            $targetCount = (int) DB::connection('sync_target')->table($tableName)->count();

            $this->comparison[$tableName]['rows1'] = $sourceCount;
            $this->comparison[$tableName]['rows2'] = $targetCount;
            $this->comparison[$tableName]['diff'] = $sourceCount - $targetCount;
            $this->comparison[$tableName]['action'] = $sourceCount > $targetCount ? 'sync' 
                : ($sourceCount < $targetCount ? 'update' : 'equal');
        } catch (\Exception $e) {
            // Silently ignore - approximate counts are better than no counts
        }
    }

    /**
     * Build a connection config array from the current Livewire form properties.
     */
    private function buildConfigFromProperties(string $prefix): ?array
    {
        return $this->connectionService->buildConfig(
            driver: $this->{$prefix . '_driver'},
            host: $this->{$prefix . '_host'},
            port: $this->{$prefix . '_port'},
            database: $this->{$prefix . '_database'},
            username: $this->{$prefix . '_username'},
            password: $this->{$prefix . '_password'},
            schema: $this->{$prefix . '_schema'},
        );
    }

    /**
     * Determine a human-readable label ("Local" or "Cloud") for a DB prefix.
     */
    private function getConnectionLabel(string $prefix): string
    {
        $driver = $this->{$prefix . '_driver'};
        $host = $this->{$prefix . '_host'};

        // SQLite is always local
        if ($driver === 'sqlite') {
            return 'Local';
        }

        // Localhost variants are local
        $localHosts = ['localhost', '127.0.0.1', '::1', ''];
        if (empty($host) || in_array(strtolower(trim($host)), $localHosts, true)) {
            return 'Local';
        }

        return 'Cloud';
    }

    /**
     * Update the dynamic labels for both database connections.
     */
    private function updateLabels(): void
    {
        $this->db1_label = $this->getConnectionLabel('db1');
        $this->db2_label = $this->getConnectionLabel('db2');
        $this->labels_match = ($this->db1_label === $this->db2_label);

        // When both labels match, build display names with host/db info for disambiguation
        if ($this->labels_match) {
            $this->db1_display = $this->buildDisplayName('db1');
            $this->db2_display = $this->buildDisplayName('db2');
        } else {
            $this->db1_display = $this->db1_label;
            $this->db2_display = $this->db2_label;
        }
    }

    /**
     * Build a disambiguated display name like "DB1 · host" or "DB1 · dbname".
     */
    private function buildDisplayName(string $prefix): string
    {
        $label = strtoupper($prefix);
        $host = $this->{$prefix . '_host'};
        $database = $this->{$prefix . '_database'};
        $driver = $this->{$prefix . '_driver'};

        // For SQLite, use the database filename
        if ($driver === 'sqlite' && $database) {
            return "{$label} · " . basename($database);
        }

        // For network DBs, use host (most distinguishing)
        if ($host) {
            return "{$label} · {$host}";
        }

        // Fallback to database name
        if ($database) {
            return "{$label} · {$database}";
        }

        return $label;
    }
}

