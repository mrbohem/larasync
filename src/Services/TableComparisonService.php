<?php

namespace MrBohem\Larasync\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableComparisonService
{
    public function __construct(
        private DatabaseConnectionService $connectionService,
    ) {
    }

    /**
     * Compare all tables between two database configs.
     * Returns an array keyed by normalized table name with rows1, rows2, diff, and action.
     * Uses batch queries to fetch all row counts efficiently.
     * Filters tables by schema if specified in config.
     */
    public function compare(array $sourceConfig, array $targetConfig): array
    {
        $sourceConn = 'temp_compare1';
        $targetConn = 'temp_compare2';

        $this->connectionService->registerConnection($sourceConn, $sourceConfig);
        $this->connectionService->registerConnection($targetConn, $targetConfig);

        // Get schema from config or use defaults
        $sourceSchema = $sourceConfig['schema'] ?? $this->connectionService->getDefaultSchema($sourceConfig['driver'] ?? 'mysql');
        $targetSchema = $targetConfig['schema'] ?? $this->connectionService->getDefaultSchema($targetConfig['driver'] ?? 'mysql');

        // Get raw table listings and filter by schema, then normalize (strip schema prefix like "main." or schema.)
        $rawSource = Schema::connection($sourceConn)->getTableListing();
        $rawTarget = Schema::connection($targetConn)->getTableListing();

        // Filter tables by schema
        $rawSource = $this->filterTablesBySchema($rawSource, $sourceSchema);
        $rawTarget = $this->filterTablesBySchema($rawTarget, $targetSchema);

        $sourceMap = $this->buildTableMap($rawSource);
        $targetMap = $this->buildTableMap($rawTarget);

        // Merge on normalized names
        $allTables = array_unique(array_merge(array_keys($sourceMap), array_keys($targetMap)));

        // Exclude ignored tables
        $ignoredTables = config('larasync.ignored_tables', []);
        $allTables = array_diff($allTables, $ignoredTables);

        // Fetch all row counts in batch queries instead of individual queries
        $sourceRowCounts = $this->getTableRowCounts($sourceConn, array_values($sourceMap), $sourceSchema);
        $targetRowCounts = $this->getTableRowCounts($targetConn, array_values($targetMap), $targetSchema);

        $comparison = [];

        foreach ($allTables as $table) {
            $sourceTable = $sourceMap[$table] ?? null;
            $targetTable = $targetMap[$table] ?? null;

            $rows1 = $sourceTable && isset($sourceRowCounts[$sourceTable])
                ? $sourceRowCounts[$sourceTable]
                : 0;

            $rows2 = $targetTable && isset($targetRowCounts[$targetTable])
                ? $targetRowCounts[$targetTable]
                : 0;

            $diff = $rows1 - $rows2;

            $comparison[$table] = [
                'rows1' => $rows1,
                'rows2' => $rows2,
                'diff' => $diff,
                'action' => $diff > 0 ? 'sync' : ($diff < 0 ? 'update' : 'equal'),
            ];
        }

        return $comparison;
    }

    /**
     * Filter tables to only include those from the specified schema.
     * For PostgreSQL: tables are in format "schema.table" or just "table"
     * For MySQL and SQLite: all tables belong to the database, schema filtering not applicable
     */
    private function filterTablesBySchema(array $tables, ?string $schema): array
    {
        // If no schema specified or schema is null, return all tables
        if ($schema === null) {
            return $tables;
        }

        $filtered = [];
        foreach ($tables as $table) {
            // Check if table has schema prefix
            if (str_contains($table, '.')) {
                $parts = explode('.', $table);
                $tableSchema = $parts[0];
                // Only include tables from the specified schema
                if ($tableSchema === $schema) {
                    $filtered[] = $table;
                }
            } else {
                // Tables without schema prefix are assumed to be from the specified schema
                $filtered[] = $table;
            }
        }
        return $filtered;
    }

    /**
     * Fetch row counts for all tables using batch queries.
     * Returns array: table_name => row_count
     * Uses database-specific system tables for efficiency.
     * Filters by schema if specified (primarily for PostgreSQL).
     */
    public function getTableRowCounts(string $connectionName, array $tables, ?string $schema = null): array
    {
        if (empty($tables)) {
            return [];
        }

        $connection = DB::connection($connectionName);
        $driver = $connection->getDriverName();

        return match ($driver) {
            'pgsql' => $this->getPostgresTableRowCounts($connection, $tables, $schema),
            'mysql' => $this->getMysqlTableRowCounts($connection, $tables),
            'sqlite' => $this->getSqliteTableRowCounts($connection, $tables),
            default => $this->getFallbackTableRowCounts($connection, $tables),
        };
    }

    /**
     * Get row counts from PostgreSQL using pg_stat_user_tables.
     * Filters by schema if specified.
     */
    private function getPostgresTableRowCounts($connection, array $tables, ?string $schema = null): array
    {
        try {
            $tableList = "'" . implode("','", array_map(fn($t) => $this->extractTableName($t), $tables)) . "'";
            
            // Build query with optional schema filter
            $schemaFilter = $schema ? "AND schemaname = '" . str_replace("'", "''", $schema) . "'" : "";
            
            $results = $connection->select("
                SELECT schemaname, relname as table_name, n_live_tup as row_count
                FROM pg_stat_user_tables
                WHERE relname IN ({$tableList})
                {$schemaFilter}
            ");

            $rowCounts = [];
            foreach ($results as $row) {
                // Use schema.table_name format if available, fallback to table_name
                $fullName = $row->schemaname . '.' . $row->table_name;
                $rowCounts[$fullName] = (int) $row->row_count;
                // Also store by table name alone for compatibility
                $rowCounts[$row->table_name] = (int) $row->row_count;
            }

            return $rowCounts;
        } catch (\Exception $e) {
            // Fallback to individual counts if system table fails
            return $this->getFallbackTableRowCounts($connection, $tables);
        }
    }

    /**
     * Get row counts from MySQL using information_schema.TABLES.
     */
    private function getMysqlTableRowCounts($connection, array $tables): array
    {
        try {
            $tableList = "'" . implode("','", array_map(fn($t) => $this->extractTableName($t), $tables)) . "'";
            $database = $connection->getDatabaseName();

            $results = $connection->select("
                SELECT TABLE_NAME, TABLE_ROWS
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ({$tableList})
            ", [$database]);

            $rowCounts = [];
            foreach ($results as $row) {
                // MySQL information_schema may return NULL for some engines; use 0 as fallback
                $count = $row->TABLE_ROWS !== null ? (int) $row->TABLE_ROWS : 0;
                $rowCounts[$row->TABLE_NAME] = $count;
            }

            return $rowCounts;
        } catch (\Exception $e) {
            // Fallback to individual counts if system table fails
            return $this->getFallbackTableRowCounts($connection, $tables);
        }
    }

    /**
     * Get row counts from SQLite using a single UNION ALL query.
     * This batches all table counts into one query for efficiency.
     */
    private function getSqliteTableRowCounts($connection, array $tables): array
    {
        if (empty($tables)) {
            return [];
        }

        try {
            // Build a UNION ALL query to count all tables in a single round-trip
            $countClauses = [];
            foreach ($tables as $table) {
                $tableName = $this->extractTableName($table);
                // Escape table name properly for SQLite
                $escapedTable = '"' . str_replace('"', '""', $tableName) . '"';
                $countClauses[] = "SELECT '{$tableName}' as table_name, COUNT(*) as row_count FROM {$escapedTable}";
            }

            $query = implode(' UNION ALL ', $countClauses);
            $results = $connection->select($query);

            $rowCounts = [];
            foreach ($results as $row) {
                $tableName = $row->table_name;
                $count = (int) $row->row_count;
                $rowCounts[$tableName] = $count;
                
                // Also store with potential schema prefix for compatibility
                foreach ($tables as $table) {
                    if ($this->extractTableName($table) === $tableName) {
                        $rowCounts[$table] = $count;
                    }
                }
            }

            return $rowCounts;
        } catch (\Exception $e) {
            // Fallback if UNION ALL approach fails
            return $this->getFallbackTableRowCounts($connection, $tables);
        }
    }

    /**
     * Fallback: count rows from each table individually.
     */
    private function getFallbackTableRowCounts($connection, array $tables): array
    {
        $rowCounts = [];

        foreach ($tables as $table) {
            try {
                $tableName = $this->extractTableName($table);
                $count = (int) $connection->table($tableName)->count();
                $rowCounts[$table] = $count;
                $rowCounts[$tableName] = $count;
            } catch (\Exception $e) {
                $rowCounts[$table] = 0;
                $rowCounts[$tableName] = 0;
            }
        }

        return $rowCounts;
    }

    /**
     * Extract the actual table name from potentially schema-qualified names.
     */
    private function extractTableName(string $table): string
    {
        if (str_contains($table, '.')) {
            $parts = explode('.', $table);
            return end($parts);
        }
        return $table;
    }

    /**
     * Strip schema/database prefix from table name.
     * e.g. "main.users" → "users", "public.posts" → "posts", "mydb.public.posts" → "posts"
     */
    public function normalizeTableName(string $table): string
    {
        // Handle PostgreSQL schema.table format or database.schema.table
        if (str_contains($table, '.')) {
            $parts = explode('.', $table);
            // Return the last part (the actual table name)
            return end($parts);
        }
        return $table;
    }

    /**
     * Build a lookup map: normalized_name => original_name
     * This preserves the schema-qualified name for proper querying.
     */
    private function buildTableMap(array $rawTables): array
    {
        $map = [];
        foreach ($rawTables as $t) {
            $normalized = $this->normalizeTableName($t);
            // Store the original (potentially schema-qualified) name
            $map[$normalized] = $t;
        }
        return $map;
    }
}
