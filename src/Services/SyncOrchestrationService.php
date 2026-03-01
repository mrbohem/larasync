<?php

namespace MrBohem\Larasync\Services;

use Illuminate\Support\Facades\Log;

class SyncOrchestrationService
{
    public function __construct(
        private DatabaseConnectionService $connectionService,
        private TableComparisonService $comparisonService,
        private TableSchemaService $schemaService,
        private TableDependencyService $dependencyService,
    ) {}

    /**
     * Detect missing tables and schema mismatches from comparison data.
     *
     * @return array{missing_tables: array, schema_mismatch_tables: array}
     */
    public function detectTableIssues(array $comparison): array
    {
        $missingTables = [];
        $schemaMismatchTables = [];

        foreach ($comparison as $table => $data) {
            if (!empty($data['missing_in_target'])) {
                $missingTables[] = $table;
            } elseif (!empty($data['missing_columns']) || !empty($data['type_mismatches'])) {
                $schemaMismatchTables[] = $table;
            }
        }

        return [
            'missing_tables' => $missingTables,
            'schema_mismatch_tables' => $schemaMismatchTables,
        ];
    }

    /**
     * Create all missing tables in the target, collecting deferred FK constraints.
     *
     * @return array{logs: array, deferred_fks: array, updated_comparison: array}
     */
    public function createMissingTables(
        array $missingTables,
        array $comparison,
        array $sourceConfig,
        array $targetConfig
    ): array {
        $logs = [];
        $allDeferredFks = [];

        foreach ($missingTables as $table) {
            $result = $this->schemaService->createTableFromSource($sourceConfig, $targetConfig, $table);
            if ($result['success']) {
                $logs[] = "✅ Created table: {$table}";
                if (isset($comparison[$table])) {
                    $comparison[$table]['missing_in_target'] = false;
                }
                if (!empty($result['deferred_fks'])) {
                    $allDeferredFks = array_merge($allDeferredFks, $result['deferred_fks']);
                }
            } else {
                $logs[] = "❌ Failed to create table {$table}: {$result['message']}";
            }
        }

        // Apply deferred FK constraints now that all tables exist
        if (!empty($allDeferredFks)) {
            $logs[] = "🔗 Binding " . count($allDeferredFks) . " deferred foreign key(s)...";
            $fkResult = $this->schemaService->applyDeferredForeignKeys($allDeferredFks, $targetConfig);
            if ($fkResult['applied'] > 0) {
                $logs[] = "✅ Bound {$fkResult['applied']} foreign key(s)";
            }
            if ($fkResult['failed'] > 0) {
                $logs[] = "⚠️ {$fkResult['failed']} foreign key(s) could not be bound";
                foreach ($fkResult['errors'] as $err) {
                    $logs[] = "  ↳ {$err}";
                }
            }
        }

        return [
            'logs' => $logs,
            'deferred_fks' => $allDeferredFks,
            'updated_comparison' => $comparison,
        ];
    }

    /**
     * Fix schema mismatch tables by dropping and recreating them.
     *
     * @return array{logs: array, updated_comparison: array}
     */
    public function fixSchemaMismatches(
        array $schemaMismatchTables,
        array $comparison,
        array $sourceConfig,
        array $targetConfig
    ): array {
        $logs = [];

        foreach ($schemaMismatchTables as $table) {
            try {
                $targetConn = 'sync_target_match';
                $this->connectionService->registerConnection($targetConn, $targetConfig);
                $this->schemaService->dropTable($targetConn, $table);
                $createResult = $this->schemaService->createTableFromSource($sourceConfig, $targetConfig, $table);
                if ($createResult['success']) {
                    $logs[] = "✅ Fixed schema for: {$table}";
                    if (isset($comparison[$table])) {
                        $comparison[$table]['missing_columns'] = [];
                        $comparison[$table]['type_mismatches'] = [];
                    }
                } else {
                    $logs[] = "❌ Failed to fix schema for {$table}: {$createResult['message']}";
                }
            } catch (\Exception $e) {
                $logs[] = "❌ Failed to fix schema for {$table}: " . $e->getMessage();
            }
        }

        return [
            'logs' => $logs,
            'updated_comparison' => $comparison,
        ];
    }

    /**
     * Build the ordered list of tables to sync, excluding skip tables and respecting FK dependencies.
     *
     * @return array{tables: array, logs: array}
     */
    public function buildSyncQueue(
        array $comparison,
        array $skipTables,
        array $targetConfig
    ): array {
        $logs = [];

        $tables = array_keys(array_filter($comparison, function ($data, $table) use ($skipTables) {
            return empty($data['missing_in_target']) && !in_array($table, $skipTables);
        }, ARRAY_FILTER_USE_BOTH));

        // Reorder tables based on foreign key dependencies
        try {
            $orderedTables = $this->dependencyService->getSyncOrder($targetConfig, $tables);

            if ($orderedTables !== $tables) {
                $logs[] = "📋 Tables reordered by dependencies:";
                $logs[] = "   Sync order: " . implode(' → ', $orderedTables);
                $tables = $orderedTables;
            } else {
                $logs[] = "📋 No dependencies detected, syncing in original order";
            }
        } catch (\Exception $e) {
            $logs[] = "⚠️ Could not analyze dependencies, syncing in original order";
            Log::warning("Dependency analysis failed: " . $e->getMessage());
        }

        return [
            'tables' => $tables,
            'logs' => $logs,
        ];
    }
}
