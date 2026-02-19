<?php

namespace MrBohem\Larasync\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use MrBohem\Larasync\Support\SyncResult;

class TableSyncService
{
    private const CHUNK_SIZE = 500;

    public function __construct(
        private DatabaseConnectionService $connectionService,
        private TableComparisonService $comparisonService,
    ) {
    }

    /**
     * Sync a single table: truncate target, copy all rows from source.
     */
    public function syncTable(string $tableName, array $sourceConfig, array $targetConfig): SyncResult
    {
        $sourceConn = 'sync_source';
        $targetConn = 'sync_target';

        try {
            $this->connectionService->registerConnection($sourceConn, $sourceConfig);
            $this->connectionService->registerConnection($targetConn, $targetConfig);

            $targetDriver = $targetConfig['driver'] ?? 'mysql';

            // Get the raw table listings to find schema-qualified names
            $sourceRawTables = Schema::connection($sourceConn)->getTableListing();
            $targetRawTables = Schema::connection($targetConn)->getTableListing();

            // Find the actual table names (with schema if needed)
            $sourceTableName = $this->findActualTableName($tableName, $sourceRawTables);
            $targetTableName = $this->findActualTableName($tableName, $targetRawTables);

            if (!$sourceTableName || !$targetTableName) {
                return new SyncResult(
                    success: false,
                    rowCount: 0,
                    message: "Sync failed: Could not find table {$tableName} in source or target database",
                );
            }

            // Get data from source
            $records = DB::connection($sourceConn)->table($sourceTableName)->get();
            $data = $records->map(fn($item) => (array) $item)->toArray();
            $rowCount = count($data);

            // Extract unqualified table name once for all operations
            $unqualifiedTableName = $this->extractTableName($targetTableName);

            // Sync with driver-specific handling
            if ($targetDriver === 'pgsql') {
                $this->syncTablePostgreSQL($targetConn, $unqualifiedTableName, $data);
            } else {
                $this->syncTableDefault($targetConn, $unqualifiedTableName, $data);
            }

            return new SyncResult(
                success: true,
                rowCount: $rowCount,
                message: "Synced {$rowCount} rows to {$tableName}",
            );
        } catch (\Exception $e) {
            Log::error("Table sync error {$tableName}: " . $e->getMessage());
            return $this->createErrorResult($tableName, $e);
        }
    }

    /**
     * Create a properly formatted error result with helpful context.
     */
    private function createErrorResult(string $tableName, \Exception $e): SyncResult
    {
        $errorMessage = "Sync failed for {$tableName}: " . $e->getMessage();

        $errorLower = strtolower($e->getMessage());
        if (str_contains($errorLower, 'foreign key') || str_contains($errorLower, 'constraint')) {
            $errorMessage .= "\n\n💡 Tip: Foreign key constraint violation. Tables must be synced in dependency order. "
                . "Use 'Sync All Tables' for automatic ordering (parent tables first).";
        }

        return new SyncResult(
            success: false,
            rowCount: 0,
            message: $errorMessage,
        );
    }

    /**
     * Sync a table for PostgreSQL with trigger disabling for data loading.
     * Disables triggers at session level to bypass FK checks without requiring DEFERRABLE constraints.
     */
    private function syncTablePostgreSQL(string $connName, string $tableName, array $data): void
    {
        $connection = DB::connection($connName);
        
        try {
            // Disable triggers at session level BEFORE transaction to avoid aborted state
            $connection->statement('SET session_replication_role = replica');
            
            // Perform sync in transaction
            $connection->transaction(function () use ($connection, $tableName, $data) {
                $this->truncateTable($connection, $tableName);
                $this->insertData($connection, $tableName, $data);
            });
        } finally {
            // Always reset role after transaction completes
            $this->resetPostgreSQLRole($connection);
        }
    }

    /**
     * Truncate a table, gracefully handling if it doesn't exist.
     */
    private function truncateTable($connection, string $tableName): void
    {
        try {
            $connection->statement('TRUNCATE TABLE ' . $tableName . ' CASCADE');
        } catch (\Exception $e) {
            if (!str_contains(strtolower($e->getMessage()), 'does not exist')) {
                throw $e;
            }
            // Table doesn't exist - skip truncate
        }
    }

    /**
     * Insert data in optimized chunks.
     */
    private function insertData($connection, string $tableName, array $data): void
    {
        foreach (array_chunk($data, self::CHUNK_SIZE) as $chunk) {
            $connection->table($tableName)->insert($chunk);
        }
    }

    /**
     * Safely reset PostgreSQL replication role.
     */
    private function resetPostgreSQLRole($connection): void
    {
        try {
            $connection->statement('SET session_replication_role = default');
        } catch (\Exception $e) {
            Log::warning("Could not reset session_replication_role: " . $e->getMessage());
        }
    }

    /**
     * Extract unqualified table name (remove schema prefix).
     * E.g., "public.doctors" → "doctors"
     */
    private function extractTableName(string $tableName): string
    {
        if (str_contains($tableName, '.')) {
            return substr($tableName, strpos($tableName, '.') + 1);
        }
        return $tableName;
    }

    /**
     * Sync a table for MySQL/SQLite using schema constraint handling.
     */
    private function syncTableDefault(string $connName, string $tableName, array $data): void
    {
        $connection = DB::connection($connName);
        Schema::connection($connName)->disableForeignKeyConstraints();
        
        try {
            $connection->table($tableName)->truncate();
            $this->insertData($connection, $tableName, $data);
        } finally {
            Schema::connection($connName)->enableForeignKeyConstraints();
        }
    }

    /**
     * Find the actual table name (with schema if needed) from a list using normalized name.
     */
    private function findActualTableName(string $normalizedName, array $rawTables): ?string
    {
        foreach ($rawTables as $table) {
            if ($this->comparisonService->normalizeTableName($table) === $normalizedName) {
                return $table;
            }
        }
        return null;
    }
}
