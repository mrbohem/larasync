<?php

namespace MrBohem\Larasync\Services;

use Illuminate\Support\Facades\DB;

class TableDependencyService
{
    public function __construct(
        private DatabaseConnectionService $connectionService,
    ) {
    }

    /**
     * Analyze foreign key dependencies and return tables in sync order.
     * Parent tables (referenced) should be synced before child tables (referencing).
     */
    public function getSyncOrder(array $config, array $tablesToSync): array
    {
        $driver = $config['driver'] ?? 'mysql';

        $dependencies = match($driver) {
            'pgsql' => $this->getPostgreSQLDependencies($config, $tablesToSync),
            'mysql' => $this->getMySQLDependencies($config, $tablesToSync),
            'sqlite' => $this->getSQLiteDependencies($config, $tablesToSync),
            default => [],  // Unknown driver, no dependencies
        };

        return $this->topologicalSort($tablesToSync, $dependencies);
    }

    /**
     * Get foreign key dependencies from PostgreSQL.
     */
    private function getPostgreSQLDependencies(array $config, array $tablesToSync): array
    {
        try {
            $connName = 'fk_analysis_pg';
            $this->connectionService->registerConnection($connName, $config);

            // Get schema from config or use default
            $schema = $config['schema'] ?? $this->connectionService->getDefaultSchema('pgsql');

            $query = "
                SELECT DISTINCT
                    tc.table_name,
                    ccu.table_name AS referenced_table_name
                FROM information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                    AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                    AND tc.table_schema = ?
            ";

            $results = DB::connection($connName)->select($query, [$schema]);
            
            return $this->buildDependencyMap($results, $tablesToSync);
        } catch (\Exception $e) {
            // Safe fallback: return empty dependencies, sync in original order
            return [];
        }
    }

    /**
     * Get foreign key dependencies from MySQL.
     */
    private function getMySQLDependencies(array $config, array $tablesToSync): array
    {
        try {
            $connName = 'fk_analysis_mysql';
            $this->connectionService->registerConnection($connName, $config);

            $database = $config['database'];

            $query = "
                SELECT DISTINCT
                    TABLE_NAME,
                    REFERENCED_TABLE_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                    AND TABLE_NAME IN (" . implode(',', array_fill(0, count($tablesToSync), '?')) . ")
            ";

            $params = [$database, ...$tablesToSync];
            $results = DB::connection($connName)->select($query, $params);

            // Convert MySQL results to standard format
            $mappedResults = array_map(fn($row) => (object)[
                'table_name' => $row->TABLE_NAME,
                'referenced_table_name' => $row->REFERENCED_TABLE_NAME
            ], $results);

            return $this->buildDependencyMap($mappedResults, $tablesToSync);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Build dependency map from query results.
     * Shared logic for MySQL and PostgreSQL.
     */
    private function buildDependencyMap(array $results, array $tablesToSync): array
    {
        $dependencies = [];
        $normalizedTables = array_flip(array_map(fn($t) => $this->stripSchema($t), $tablesToSync));

        foreach ($results as $row) {
            $childTable = $row->table_name;
            $parentTable = $row->referenced_table_name;

            // Only track if both tables are in our sync list and not self-referencing
            if (isset($normalizedTables[$childTable]) && 
                isset($normalizedTables[$parentTable]) &&
                $childTable !== $parentTable) {

                if (!isset($dependencies[$childTable])) {
                    $dependencies[$childTable] = [];
                }

                if (!in_array($parentTable, $dependencies[$childTable], true)) {
                    $dependencies[$childTable][] = $parentTable;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Get foreign key dependencies from SQLite using PRAGMA.
     */
    private function getSQLiteDependencies(array $config, array $tablesToSync): array
    {
        try {
            $connName = 'fk_analysis_sqlite';
            $this->connectionService->registerConnection($connName, $config);

            $dependencies = [];
            $normalizedTables = array_map(fn($t) => $this->stripSchema($t), $tablesToSync);

            foreach ($tablesToSync as $table) {
                $normalizedTable = $this->stripSchema($table);
                $dependencies[$normalizedTable] = [];

                try {
                    // SQLite PRAGMA to get FK info for this table
                    $results = DB::connection($connName)->select(
                        "PRAGMA foreign_key_list({$normalizedTable})"
                    );

                    foreach ($results as $row) {
                        $parentTable = $row->table ?? null;
                        
                        if ($parentTable && in_array($parentTable, $normalizedTables, true)) {
                            if (!in_array($parentTable, $dependencies[$normalizedTable], true)) {
                                $dependencies[$normalizedTable][] = $parentTable;
                            }
                        }
                    }
                } catch (\Exception) {
                    // Table has no FKs or doesn't exist, continue
                    continue;
                }
            }

            return $dependencies;
        } catch (\Exception $e) {
            // Safe fallback: return empty dependencies
            return [];
        }
    }

    /**
     * Topologically sort tables based on dependencies.
     * Returns tables in order where dependencies come before dependents.
     */
    private function topologicalSort(array $tables, array $dependencies): array
    {
        // Create a map of normalized to original names
        $nameMap = [];
        foreach ($tables as $table) {
            $normalized = $this->stripSchema($table);
            $nameMap[$normalized] = $table;
        }

        $visited = [];
        $sorted = [];
        $visiting = [];

        foreach ($nameMap as $normalized => $original) {
            if (!isset($visited[$normalized])) {
                $this->visit($normalized, $dependencies, $visited, $visiting, $sorted);
            }
        }

        // Map sorted normalized names back to original table names
        return array_map(fn($normalized) => $nameMap[$normalized], $sorted);
    }

    /**
     * Depth-first search for topological sort.
     */
    private function visit(
        string $table,
        array $dependencies,
        array &$visited,
        array &$visiting,
        array &$sorted
    ): void {
        if (isset($visited[$table])) {
            return;
        }

        if (isset($visiting[$table])) {
            // Circular dependency detected, skip
            return;
        }

        $visiting[$table] = true;

        // Visit all dependencies first
        if (isset($dependencies[$table])) {
            foreach ($dependencies[$table] as $dependency) {
                $normalizedDep = $this->stripSchema($dependency);
                if (!isset($visited[$normalizedDep])) {
                    $this->visit($normalizedDep, $dependencies, $visited, $visiting, $sorted);
                }
            }
        }

        unset($visiting[$table]);
        $visited[$table] = true;
        $sorted[] = $table;
    }

    /**
     * Strip schema prefix from table name, extracting the last part.
     * Uses faster strrev + strpos approach instead of explode.
     */
    private function stripSchema(string $table): string
    {
        if (str_contains($table, '.')) {
            $lastDot = strrpos($table, '.');
            return substr($table, $lastDot + 1);
        }
        return $table;
    }


}
