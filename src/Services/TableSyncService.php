<?php

namespace MrBohem\Larasync\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use MrBohem\Larasync\Support\SyncResult;

class TableSyncService
{
    private const CHUNK_SIZE = 1000;
    private const BATCH_INSERT_SIZE = 500;
    private const PROGRESS_CHUNK_SIZE = 5000;

    public function __construct(
        private DatabaseConnectionService $connectionService,
        private TableComparisonService $comparisonService,
    ) {
    }

    /**
     * Sync a single table: truncate target, stream copy all rows from source.
     * Uses chunked queries to avoid memory exhaustion on large tables.
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

            // Extract unqualified table name once for all operations
            $unqualifiedTableName = $this->extractTableName($targetTableName);

            // Truncate and count total rows using streaming
            $rowCount = $this->countTableRows(DB::connection($sourceConn), $sourceTableName);

            // Sync with driver-specific handling (streams data in chunks)
            if ($targetDriver === 'pgsql') {
                $this->syncTablePostgreSQL($sourceConn, $targetConn, $sourceTableName, $unqualifiedTableName);
            } else {
                $this->syncTableDefault($sourceConn, $targetConn, $sourceTableName, $unqualifiedTableName);
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
     * Sync a chunk of rows for a single table (used for progress tracking on large tables).
     * Each call syncs PROGRESS_CHUNK_SIZE rows starting from $offset.
     * First chunk (offset=0) truncates the target table.
     */
    public function syncTableChunk(string $tableName, array $sourceConfig, array $targetConfig, int $offset): array
    {
        $sourceConn = 'sync_source';
        $targetConn = 'sync_target';

        try {
            $this->connectionService->registerConnection($sourceConn, $sourceConfig);
            $this->connectionService->registerConnection($targetConn, $targetConfig);

            $targetDriver = $targetConfig['driver'] ?? 'mysql';

            $sourceRawTables = Schema::connection($sourceConn)->getTableListing();
            $targetRawTables = Schema::connection($targetConn)->getTableListing();

            $sourceTableName = $this->findActualTableName($tableName, $sourceRawTables);
            $targetTableName = $this->findActualTableName($tableName, $targetRawTables);

            if (!$sourceTableName || !$targetTableName) {
                return [
                    'success' => false,
                    'message' => "Could not find table {$tableName} in source or target database",
                    'done' => true,
                ];
            }

            $unqualifiedTableName = $this->extractTableName($targetTableName);
            $sourceConnection = DB::connection($sourceConn);
            $targetConnection = DB::connection($targetConn);

            // On first chunk, truncate target table
            if ($offset === 0) {
                if ($targetDriver === 'pgsql') {
                    $targetConnection->statement('SET session_replication_role = replica');
                    try {
                        $this->truncateTable($targetConnection, $unqualifiedTableName);
                    } finally {
                        $this->resetPostgreSQLRole($targetConnection);
                    }
                } else {
                    Schema::connection($targetConn)->disableForeignKeyConstraints();
                    try {
                        $targetConnection->table($unqualifiedTableName)->truncate();
                    } finally {
                        Schema::connection($targetConn)->enableForeignKeyConstraints();
                    }
                }
            }

            // Get order column for consistent pagination
            $columns = Schema::connection($sourceConn)->getColumnListing($sourceTableName);
            $orderByColumn = !empty($columns) ? $columns[0] : null;

            // Fetch chunk of rows using offset pagination
            $query = $sourceConnection->table($sourceTableName);
            if ($orderByColumn) {
                $query = $query->orderBy($orderByColumn);
            }
            $rows = $query->skip($offset)->take(self::PROGRESS_CHUNK_SIZE)->get();

            $rowCount = $rows->count();

            if ($rowCount > 0) {
                $data = $rows->map(fn($r) => (array) $r)->toArray();

                if ($targetDriver === 'pgsql') {
                    $targetConnection->statement('SET session_replication_role = replica');
                    try {
                        foreach (array_chunk($data, self::BATCH_INSERT_SIZE) as $batch) {
                            $targetConnection->table($unqualifiedTableName)->insert($batch);
                        }
                    } finally {
                        $this->resetPostgreSQLRole($targetConnection);
                    }
                } else {
                    Schema::connection($targetConn)->disableForeignKeyConstraints();
                    try {
                        foreach (array_chunk($data, self::BATCH_INSERT_SIZE) as $batch) {
                            $targetConnection->table($unqualifiedTableName)->insert($batch);
                        }
                    } finally {
                        Schema::connection($targetConn)->enableForeignKeyConstraints();
                    }
                }
            }

            $newOffset = $offset + $rowCount;
            $done = $rowCount < self::PROGRESS_CHUNK_SIZE;

            return [
                'success' => true,
                'synced' => $rowCount,
                'offset' => $newOffset,
                'done' => $done,
            ];
        } catch (\Exception $e) {
            Log::error("Table chunk sync error {$tableName}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Chunk sync failed for {$tableName}: " . $e->getMessage(),
                'done' => true,
            ];
        }
    }

    /**
     * Count total rows in a table without loading data.
     */
    private function countTableRows($connection, string $tableName): int
    {
        try {
            return (int) $connection->table($tableName)->count();
        } catch (\Exception $e) {
            return 0;
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
     * Sync a table for PostgreSQL with chunked streaming.
     * Disables triggers at session level to bypass FK checks without requiring DEFERRABLE constraints.
     */
    private function syncTablePostgreSQL(string $sourceConn, string $targetConn, string $sourceTableName, string $tableName): void
    {
        $sourceConnection = DB::connection($sourceConn);
        $targetConnection = DB::connection($targetConn);
        
        try {
            // Disable triggers at session level BEFORE transaction to avoid aborted state
            $targetConnection->statement('SET session_replication_role = replica');
            
            // Perform sync in transaction
            $targetConnection->transaction(function () use ($sourceConnection, $targetConnection, $sourceTableName, $tableName) {
                $this->truncateTable($targetConnection, $tableName);
                $this->insertDataChunked($sourceConnection, $targetConnection, $sourceTableName, $tableName);
            });
        } finally {
            // Always reset role after transaction completes
            $this->resetPostgreSQLRole($targetConnection);
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

    /**
     * Sync a table for MySQL/SQLite using schema constraint handling with chunked streaming.
     */
    private function syncTableDefault(string $sourceConn, string $targetConn, string $sourceTableName, string $tableName): void
    {
        $sourceConnection = DB::connection($sourceConn);
        $targetConnection = DB::connection($targetConn);
        Schema::connection($targetConn)->disableForeignKeyConstraints();
        
        try {
            $targetConnection->table($tableName)->truncate();
            $this->insertDataChunked($sourceConnection, $targetConnection, $sourceTableName, $tableName);
        } finally {
            Schema::connection($targetConn)->enableForeignKeyConstraints();
        }
    }

    /**
     * Insert data in chunks using streaming to avoid memory exhaustion.
     * Uses cursor to stream rows from source and insert in batches to target.
     * Adds orderBy clause to ensure chunk() works on tables without proper primary keys.
     */
    private function insertDataChunked($sourceConnection, $targetConnection, string $sourceTableName, string $tableName): void
    {
        $batch = [];
        $count = 0;

        // Get the first column name for ordering (required by chunk() method)
        $columns = Schema::connection('sync_source')->getColumnListing($sourceTableName);
        $orderByColumn = !empty($columns) ? $columns[0] : null;

        // Build query with orderBy clause (required for chunk() to work on tables without primary key)
        $query = $sourceConnection->table($sourceTableName);
        if ($orderByColumn) {
            $query = $query->orderBy($orderByColumn);
        }

        // Stream data in chunks (this doesn't load all data into memory at once)
        $query->chunk(self::CHUNK_SIZE, function ($rows) use ($targetConnection, $tableName, &$batch, &$count) {
            foreach ($rows as $row) {
                $batch[] = (array) $row;
                $count++;

                // Insert when batch reaches BATCH_INSERT_SIZE
                if (count($batch) >= self::BATCH_INSERT_SIZE) {
                    $targetConnection->table($tableName)->insert($batch);
                    $batch = [];
                }
            }

            // Insert remaining rows in batch
            if (!empty($batch)) {
                $targetConnection->table($tableName)->insert($batch);
                $batch = [];
            }

            // Allow garbage collection if available
            gc_collect_cycles();
        });
    }
}
