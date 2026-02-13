<?php

namespace MrBohem\Larasync\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use MrBohem\Larasync\Support\SyncResult;

class TableSyncService
{
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

            $bareTable = $this->comparisonService->normalizeTableName($tableName);

            // Get data from source
            $records = DB::connection($sourceConn)->table($bareTable)->get();
            $data = $records->map(fn($item) => (array) $item)->toArray();

            // Clear target table and insert source data
            Schema::connection($targetConn)->disableForeignKeyConstraints();
            DB::connection($targetConn)->table($bareTable)->truncate();

            foreach (array_chunk($data, 500) as $chunk) {
                DB::connection($targetConn)->table($bareTable)->insert($chunk);
            }

            Schema::connection($targetConn)->enableForeignKeyConstraints();

            return new SyncResult(
                success: true,
                rowCount: $records->count(),
                message: "Synced {$records->count()} rows to {$bareTable}",
            );
        } catch (\Exception $e) {
            Log::error("Table sync error {$tableName}: " . $e->getMessage());

            return new SyncResult(
                success: false,
                rowCount: 0,
                message: "Sync failed for {$tableName}: " . $e->getMessage(),
            );
        }
    }
}
