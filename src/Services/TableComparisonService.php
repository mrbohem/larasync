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

        // Get raw table listings and normalize (strip schema prefix like "main.")
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
            $rows1 = isset($sourceMap[$table])
                ? DB::connection($sourceConn)->table($sourceMap[$table])->count()
                : 0;

            $rows2 = isset($targetMap[$table])
                ? DB::connection($targetConn)->table($targetMap[$table])->count()
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
     * e.g. "main.users" → "users", "mydb.posts" → "posts"
     */
    public function normalizeTableName(string $table): string
    {
        return str_contains($table, '.')
            ? substr($table, strpos($table, '.') + 1)
            : $table;
    }

    /**
     * Build a lookup map: normalized_name => original_name
     */
    private function buildTableMap(array $rawTables): array
    {
        $map = [];
        foreach ($rawTables as $t) {
            $map[$this->normalizeTableName($t)] = $t;
        }
        return $map;
    }
}
