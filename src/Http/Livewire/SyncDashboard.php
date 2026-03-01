<?php

namespace MrBohem\Larasync\Http\Livewire;

use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use MrBohem\Larasync\Services\ConnectionLabelService;
use MrBohem\Larasync\Services\DatabaseConnectionService;
use MrBohem\Larasync\Services\SyncOrchestrationService;
use MrBohem\Larasync\Services\TableComparisonService;
use MrBohem\Larasync\Services\TableSyncService;
use MrBohem\Larasync\Services\TableDependencyService;
use MrBohem\Larasync\Services\TableSchemaService;

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

    // ── Missing Tables ─────────────────────────────────────────────
    public $missing_tables = [];
    public $schema_mismatch_tables = [];
    public $show_missing_tables_modal = false;
    public $pending_create_table = null;
    public $pending_create_preview = [];

    // ── Schema Warnings ────────────────────────────────────────────
    public $show_schema_modal = false;
    public $schema_warnings_table = null;

    // ── Services ───────────────────────────────────────────────────
    private DatabaseConnectionService $connectionService;
    private TableComparisonService $comparisonService;
    private TableSyncService $syncService;
    private TableDependencyService $dependencyService;
    private TableSchemaService $schemaService;
    private ConnectionLabelService $labelService;
    private SyncOrchestrationService $orchestrationService;

    public function boot()
    {
        $this->connectionService = app(DatabaseConnectionService::class);
        $this->schemaService = app(TableSchemaService::class);
        $this->comparisonService = app(TableComparisonService::class);
        $this->syncService = app(TableSyncService::class);
        $this->dependencyService = app(TableDependencyService::class);
        $this->labelService = app(ConnectionLabelService::class);
        $this->orchestrationService = app(SyncOrchestrationService::class);
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

    public function testDb(string $prefix)
    {
        $label = strtoupper($prefix);
        $connected = $this->testDbConnection($prefix);
        $this->{$prefix . '_connected'} = $connected;
        session()->flash(
            $connected ? 'success' : 'error',
            $connected ? "✅ {$label} Connected!" : "❌ {$label} Connection failed!"
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

    public function syncTable($tableName, $createMissingTable = false)
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

        $result = $this->syncService->syncTable($tableName, $sourceConfig, $targetConfig, $createMissingTable);

        if ($result->success) {
            $this->logs[] = "✅ {$result->message}";
            $this->synced_tables[] = $tableName;
            // Clear missing flag after successful creation
            if (isset($this->comparison[$tableName])) {
                $this->comparison[$tableName]['missing_in_target'] = false;
            }
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

        // Check if table is missing in target
        $isMissing = $this->comparison[$tableName]['missing_in_target'] ?? false;
        if ($isMissing) {
            $this->showCreateTablePreview($tableName);
            return;
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

        // Check for missing tables and schema mismatches
        $issues = $this->orchestrationService->detectTableIssues($this->comparison);
        $this->missing_tables = $issues['missing_tables'];
        $this->schema_mismatch_tables = $issues['schema_mismatch_tables'];

        if (!empty($this->missing_tables) || !empty($this->schema_mismatch_tables)) {
            $this->show_missing_tables_modal = true;
            return;
        }

        // No issues, proceed directly
        $this->startSyncAllTables();
    }

    /**
     * Handle user's choice for missing tables during Sync All.
     */
    public function handleMissingTablesChoice(string $action)
    {
        $this->show_missing_tables_modal = false;

        if ($action === 'cancel') {
            $this->logs[] = '⛔ Sync cancelled by user';
            $this->missing_tables = [];
            $this->schema_mismatch_tables = [];
            return;
        }

        $sourceConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db1' : 'db2');
        $targetConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db2' : 'db1');

        if ($action === 'create') {
            // Create all missing tables via orchestration service
            $createResult = $this->orchestrationService->createMissingTables(
                $this->missing_tables, $this->comparison, $sourceConfig, $targetConfig
            );
            $this->logs = array_merge($this->logs, $createResult['logs']);
            $this->comparison = $createResult['updated_comparison'];

            // Fix schema mismatch tables via orchestration service
            $fixResult = $this->orchestrationService->fixSchemaMismatches(
                $this->schema_mismatch_tables, $this->comparison, $sourceConfig, $targetConfig
            );
            $this->logs = array_merge($this->logs, $fixResult['logs']);
            $this->comparison = $fixResult['updated_comparison'];

            // Re-run comparison after creating/fixing tables
            $this->comparison = $this->comparisonService->compare($sourceConfig, $targetConfig);
            $this->missing_tables = [];
            $this->schema_mismatch_tables = [];
        }

        if ($action === 'skip') {
            $skipCount = count($this->missing_tables) + count($this->schema_mismatch_tables);
            $this->logs[] = '⚠️ Skipping ' . $skipCount . ' table(s) with issues';
            // missing_tables and schema_mismatch_tables stay set so startSyncAllTables can exclude them
        }

        // Proceed with sync (skip or create both lead to syncing)
        $this->startSyncAllTables();
    }

    /**
     * Show create table confirmation modal for single table sync.
     */
    public function showCreateTablePreview(string $tableName)
    {
        $sourceConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db1' : 'db2');
        $this->connectionService->registerConnection('preview_source', $sourceConfig);

        $this->pending_create_table = $tableName;
        $this->pending_create_preview = $this->schemaService->getTableColumns('preview_source', $tableName);
    }

    /**
     * Confirm creation and sync of a single missing table.
     */
    public function confirmCreateAndSync(string $tableName)
    {
        $this->pending_create_table = null;
        $this->pending_create_preview = [];

        $this->logs[] = "🔧 Creating table: {$tableName}...";
        $this->syncTable($tableName, createMissingTable: true);
    }

    /**
     * Cancel single table creation.
     */
    public function cancelCreateTable()
    {
        $this->pending_create_table = null;
        $this->pending_create_preview = [];
        $this->logs[] = '⛔ Table creation cancelled';
    }

    /**
     * Show schema warnings modal for a specific table.
     */
    public function showSchemaWarnings(string $tableName)
    {
        if (isset($this->comparison[$tableName])) {
            $this->schema_warnings_table = $tableName;
            $this->show_schema_modal = true;
        }
    }

    /**
     * Close schema warnings modal.
     */
    public function closeSchemaWarnings()
    {
        $this->show_schema_modal = false;
        $this->schema_warnings_table = null;
    }

    /**
     * Match target schema exactly to source by dropping and recreating.
     */
    public function matchTargetSchema(string $tableName)
    {
        $this->closeSchemaWarnings();

        $sourceConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db1' : 'db2');
        $targetConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db2' : 'db1');

        $targetConn = 'sync_target_match';
        $this->connectionService->registerConnection($targetConn, $targetConfig);

        $this->logs[] = "⚠️ Dropping target table: {$tableName} to perfectly match schema...";

        try {
            // Drop target table safely utilizing TableSchemaService
            $this->schemaService->dropTable($targetConn, $tableName);

            // Set state flag to prevent normal sync table from ignoring the missing table if createMissingTable is false
            // But we pass createMissingTable: true below, so it's fine.
            $this->logs[] = "🔧 Recreating target table: {$tableName} and syncing data...";

            // Recreate table and sync data
            $this->syncTable($tableName, createMissingTable: true);
            
            $this->logs[] = "✅ Schema successfully matched for {$tableName}";

            // Re-run comparison to show updated schema matching status
            $this->comparison = $this->comparisonService->compare($sourceConfig, $targetConfig);

        } catch (\Exception $e) {
            $this->logs[] = "❌ Failed to match schema for {$tableName}: " . $e->getMessage();
            Log::error("Match schema error {$tableName}: " . $e->getMessage());
        }
    }

    /**
     * Start the actual sync-all process (after missing table handling).
     */
    private function startSyncAllTables()
    {
        $this->sync_cancelled = false;
        $this->increaseExecutionTime(600); // 10 minutes for full sync

        // Build ordered sync queue via orchestration service
        $skipTables = array_merge($this->missing_tables, $this->schema_mismatch_tables);
        $targetConfig = $this->buildConfigFromProperties($this->sync_direction === 'db1_to_db2' ? 'db2' : 'db1');

        $queueResult = $this->orchestrationService->buildSyncQueue(
            $this->comparison, $skipTables, $targetConfig
        );
        $tables = $queueResult['tables'];
        $this->logs = array_merge($this->logs, $queueResult['logs']);

        // Clear after building the list
        $this->missing_tables = [];
        $this->schema_mismatch_tables = [];

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
        $this->missing_tables = [];
        $this->schema_mismatch_tables = [];
        $this->show_missing_tables_modal = false;
        $this->pending_create_table = null;
        $this->pending_create_preview = [];
        $this->show_schema_modal = false;
        $this->schema_warnings_table = null;
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
    /**
     * Update the dynamic labels for both database connections.
     * Delegates to ConnectionLabelService.
     */
    private function updateLabels(): void
    {
        $result = $this->labelService->computeLabels(
            ['driver' => $this->db1_driver, 'host' => $this->db1_host, 'database' => $this->db1_database],
            ['driver' => $this->db2_driver, 'host' => $this->db2_host, 'database' => $this->db2_database]
        );

        $this->db1_label = $result['db1_label'];
        $this->db2_label = $result['db2_label'];
        $this->labels_match = $result['labels_match'];
        $this->db1_display = $result['db1_display'];
        $this->db2_display = $result['db2_display'];
    }
}

