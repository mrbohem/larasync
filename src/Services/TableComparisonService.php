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
     */
    public function compare(array $sourceConfig, array $targetConfig): array
    {
        $sourceConn = 'temp_compare1';
        $targetConn = 'temp_compare2';

        $this->connectionService->registerConnection($sourceConn, $sourceConfig);
        $this->connectionService->registerConnection($targetConn, $targetConfig);

        // Get raw table listings and normalize (strip schema prefix like "main." or schema.)
        $rawSource = Schema::connection($sourceConn)->getTableListing();
        $rawTarget = Schema::connection($targetConn)->getTableListing();

        $sourceMap = $this->buildTableMap($rawSource);
        $targetMap = $this->buildTableMap($rawTarget);

        // Merge on normalized names
        $allTables = array_unique(array_merge(array_keys($sourceMap), array_keys($targetMap)));

        // Exclude ignored tables
        $ignoredTables = config('larasync.ignored_tables', []);
        $allTables = array_diff($allTables, $ignoredTables);

        $comparison = [];

        foreach ($allTables as $table) {
            $sourceTable = $sourceMap[$table] ?? null;
            $targetTable = $targetMap[$table] ?? null;

            $rows1 = $sourceTable
                ? DB::connection($sourceConn)->table($sourceTable)->count()
                : 0;

            $rows2 = $targetTable
                ? DB::connection($targetConn)->table($targetTable)->count()
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
