<?php

namespace MrBohem\Larasync\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TableSchemaService
{
    public function __construct(
        private DatabaseConnectionService $connectionService,
    ) {}

    /**
     * Get the CREATE TABLE SQL for a table from the given connection.
     * Returns raw DDL string or null on failure.
     */
    public function getCreateTableSQL(string $connectionName, string $table): ?string
    {
        try {
            $driver = DB::connection($connectionName)->getDriverName();

            return match ($driver) {
                'sqlite' => $this->getSQLiteCreateSQL($connectionName, $table),
                'mysql' => $this->getMySQLCreateSQL($connectionName, $table),
                'pgsql' => $this->getPostgreSQLCreateSQL($connectionName, $table),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error("Failed to get CREATE TABLE SQL for {$table}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get column info for a table (for preview display and cross-driver creation).
     * Returns array of ['name' => ..., 'type' => ..., 'nullable' => ...] entries.
     */
    public function getTableColumns(string $connectionName, string $table): array
    {
        try {
            return Schema::connection($connectionName)->getColumns($table);
        } catch (\Exception $e) {
            Log::error("Failed to get columns for {$table}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get foreign key constraints for a table from the given connection.
     * Returns array of ['column' => ..., 'foreign_table' => ..., 'foreign_column' => ...] entries.
     */
    public function getTableForeignKeys(string $connectionName, string $table): array
    {
        try {
            $driver = DB::connection($connectionName)->getDriverName();
            $bareTable = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;

            return match ($driver) {
                'sqlite' => $this->getSQLiteForeignKeys($connectionName, $bareTable),
                'mysql' => $this->getMySQLForeignKeys($connectionName, $bareTable),
                'pgsql' => $this->getPostgreSQLForeignKeys($connectionName, $table),
                default => [],
            };
        } catch (\Exception $e) {
            Log::error("Failed to get foreign keys for {$table}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a table on the target connection by copying the schema from source.
     * Uses native DDL for same-driver, Schema Builder fallback for cross-driver.
     * After creating the table, attempts to bind FK constraints.
     * FKs whose referenced table doesn't exist yet are returned as 'deferred_fks'.
     *
     * @return array{success: bool, message: string, deferred_fks?: array}
     */
    public function createTableFromSource(
        array $sourceConfig,
        array $targetConfig,
        string $table
    ): array {
        $sourceConn = 'schema_source';
        $targetConn = 'schema_target';

        try {
            $this->connectionService->registerConnection($sourceConn, $sourceConfig);
            $this->connectionService->registerConnection($targetConn, $targetConfig);

            $sourceDriver = $sourceConfig['driver'] ?? 'mysql';
            $targetDriver = $targetConfig['driver'] ?? 'mysql';

            // Same driver: use native DDL (includes FK constraints in DDL)
            // For SQLite native DDL already contains REFERENCES clauses,
            // but they won't fail even if the referenced table doesn't exist.
            // For MySQL/PostgreSQL, native DDL may fail if referenced table is missing.
            if ($sourceDriver === $targetDriver) {
                $result = $this->createTableNative($sourceConn, $targetConn, $table, $sourceDriver);
            } else {
                // Cross-driver: use Schema Builder (creates columns only)
                $result = $this->createTableViaSchemaBuilder($sourceConn, $targetConn, $table);
            }

            if (!$result['success']) {
                return $result;
            }

            // Now attempt to bind FK constraints
            $deferredFks = $this->bindForeignKeys($sourceConn, $targetConn, $table);

            $result['deferred_fks'] = $deferredFks;
            if (!empty($deferredFks)) {
                $deferredCount = count($deferredFks);
                $result['message'] .= " ({$deferredCount} FK(s) deferred — referenced table(s) not yet created)";
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to create table {$table}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Failed to create table {$table}: " . $e->getMessage(),
                'deferred_fks' => [],
            ];
        }
    }

    /**
     * Apply deferred foreign key constraints after all tables have been created.
     * Takes an array of deferred FK entries (from createTableFromSource results).
     *
     * @param  array  $deferredFks  Array of ['table' => ..., 'column' => ..., 'foreign_table' => ..., 'foreign_column' => ...]
     * @param  array  $targetConfig Target database config
     * @return array{applied: int, failed: int, errors: array}
     */
    public function applyDeferredForeignKeys(array $deferredFks, array $targetConfig): array
    {
        if (empty($deferredFks)) {
            return ['applied' => 0, 'failed' => 0, 'errors' => []];
        }

        $targetConn = 'schema_target';
        $this->connectionService->registerConnection($targetConn, $targetConfig);

        $applied = 0;
        $failed = 0;
        $errors = [];

        // Get current target tables to check which referenced tables now exist
        $targetTables = Schema::connection($targetConn)->getTableListing();
        $normalizedTargetTables = array_map(
            fn($t) => str_contains($t, '.') ? substr($t, strrpos($t, '.') + 1) : $t,
            $targetTables
        );

        foreach ($deferredFks as $fk) {
            $bareRefTable = str_contains($fk['foreign_table'], '.')
                ? substr($fk['foreign_table'], strrpos($fk['foreign_table'], '.') + 1)
                : $fk['foreign_table'];

            if (!in_array($bareRefTable, $normalizedTargetTables)) {
                // Referenced table still doesn't exist — skip
                $failed++;
                $errors[] = "FK on {$fk['table']}.{$fk['column']} → {$bareRefTable}.{$fk['foreign_column']}: referenced table still missing";
                continue;
            }

            $bareTable = str_contains($fk['table'], '.')
                ? substr($fk['table'], strrpos($fk['table'], '.') + 1)
                : $fk['table'];

            try {
                Schema::connection($targetConn)->table($bareTable, function ($blueprint) use ($fk, $bareRefTable) {
                    $blueprint->foreign($fk['column'])
                        ->references($fk['foreign_column'])
                        ->on($bareRefTable);
                });
                $applied++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "FK on {$fk['table']}.{$fk['column']}: " . $e->getMessage();
                Log::warning("Failed to apply deferred FK: " . $e->getMessage());
            }
        }

        return ['applied' => $applied, 'failed' => $failed, 'errors' => $errors];
    }

    // ────────────────────────────────────────────────────────────────
    //  Native DDL Methods (same driver)
    // ────────────────────────────────────────────────────────────────

    private function getSQLiteCreateSQL(string $connectionName, string $table): ?string
    {
        // Strip schema prefix (e.g., "main.users" → "users")
        $bareTable = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;

        $result = DB::connection($connectionName)
            ->selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$bareTable]);

        return $result?->sql;
    }

    private function getMySQLCreateSQL(string $connectionName, string $table): ?string
    {
        $result = DB::connection($connectionName)
            ->selectOne("SHOW CREATE TABLE `{$table}`");

        return $result ? ($result->{'Create Table'} ?? null) : null;
    }

    private function getPostgreSQLCreateSQL(string $connectionName, string $table): ?string
    {
        // PostgreSQL doesn't have a simple SHOW CREATE TABLE.
        // We build a reasonable approximation from information_schema.
        $schema = 'public';
        $bareTable = $table;

        if (str_contains($table, '.')) {
            [$schema, $bareTable] = explode('.', $table, 2);
        }

        $columns = DB::connection($connectionName)->select(
            "SELECT column_name, data_type, is_nullable, column_default, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ?
             ORDER BY ordinal_position",
            [$schema, $bareTable]
        );

        if (empty($columns)) {
            return null;
        }

        $colDefs = [];
        foreach ($columns as $col) {
            $type = strtoupper($col->data_type);
            if ($col->character_maximum_length) {
                $type .= "({$col->character_maximum_length})";
            }
            $def = "\"{$col->column_name}\" {$type}";
            if ($col->is_nullable === 'NO') {
                $def .= ' NOT NULL';
            }
            if ($col->column_default !== null) {
                $def .= " DEFAULT {$col->column_default}";
            }
            $colDefs[] = $def;
        }

        return "CREATE TABLE \"{$bareTable}\" (\n  " . implode(",\n  ", $colDefs) . "\n)";
    }

    // ────────────────────────────────────────────────────────────────
    //  Foreign Key Extraction Methods
    // ────────────────────────────────────────────────────────────────

    private function getSQLiteForeignKeys(string $connectionName, string $table): array
    {
        $results = DB::connection($connectionName)->select("PRAGMA foreign_key_list('{$table}')");
        $fks = [];
        foreach ($results as $row) {
            $fks[] = [
                'column' => $row->from,
                'foreign_table' => $row->table,
                'foreign_column' => $row->to,
            ];
        }
        return $fks;
    }

    private function getMySQLForeignKeys(string $connectionName, string $table): array
    {
        $database = DB::connection($connectionName)->getDatabaseName();
        $results = DB::connection($connectionName)->select(
            "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$database, $table]
        );

        $fks = [];
        foreach ($results as $row) {
            $fks[] = [
                'column' => $row->COLUMN_NAME,
                'foreign_table' => $row->REFERENCED_TABLE_NAME,
                'foreign_column' => $row->REFERENCED_COLUMN_NAME,
            ];
        }
        return $fks;
    }

    private function getPostgreSQLForeignKeys(string $connectionName, string $table): array
    {
        $schema = 'public';
        $bareTable = $table;
        if (str_contains($table, '.')) {
            [$schema, $bareTable] = explode('.', $table, 2);
        }

        $results = DB::connection($connectionName)->select(
            "SELECT
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
             FROM information_schema.table_constraints AS tc
             JOIN information_schema.key_column_usage AS kcu
                 ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
             JOIN information_schema.constraint_column_usage AS ccu
                 ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
             WHERE tc.constraint_type = 'FOREIGN KEY'
                 AND tc.table_schema = ? AND tc.table_name = ?",
            [$schema, $bareTable]
        );

        $fks = [];
        foreach ($results as $row) {
            $fks[] = [
                'column' => $row->column_name,
                'foreign_table' => $row->foreign_table_name,
                'foreign_column' => $row->foreign_column_name,
            ];
        }
        return $fks;
    }

    // ────────────────────────────────────────────────────────────────
    //  Foreign Key Binding (after table creation)
    // ────────────────────────────────────────────────────────────────

    /**
     * Attempt to bind FK constraints from source to already-created target table.
     * Skips FKs whose referenced table doesn't exist in target yet.
     * Returns array of deferred FK entries.
     */
    private function bindForeignKeys(string $sourceConn, string $targetConn, string $table): array
    {
        $foreignKeys = $this->getTableForeignKeys($sourceConn, $table);

        if (empty($foreignKeys)) {
            return [];
        }

        $bareTable = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;

        // Get existing target tables
        $targetTables = Schema::connection($targetConn)->getTableListing();
        $normalizedTargetTables = array_map(
            fn($t) => str_contains($t, '.') ? substr($t, strrpos($t, '.') + 1) : $t,
            $targetTables
        );

        $deferred = [];

        // For SQLite with native DDL, FKs are embedded in the CREATE TABLE statement,
        // but SQLite doesn't enforce them unless PRAGMA foreign_keys = ON.
        // For native DDL tables, FKs are already in the DDL so skip re-adding.
        $driver = DB::connection($targetConn)->getDriverName();
        if ($driver === 'sqlite') {
            // SQLite native DDL already includes REFERENCES. Check which ones
            // reference missing tables — return those as deferred for tracking.
            foreach ($foreignKeys as $fk) {
                $bareRefTable = str_contains($fk['foreign_table'], '.')
                    ? substr($fk['foreign_table'], strrpos($fk['foreign_table'], '.') + 1)
                    : $fk['foreign_table'];

                if (!in_array($bareRefTable, $normalizedTargetTables)) {
                    $deferred[] = array_merge($fk, ['table' => $bareTable]);
                }
            }
            return $deferred;
        }

        // For MySQL/PostgreSQL — add FK constraints via Schema Builder
        foreach ($foreignKeys as $fk) {
            $bareRefTable = str_contains($fk['foreign_table'], '.')
                ? substr($fk['foreign_table'], strrpos($fk['foreign_table'], '.') + 1)
                : $fk['foreign_table'];

            if (!in_array($bareRefTable, $normalizedTargetTables)) {
                // Referenced table doesn't exist yet — defer
                $deferred[] = array_merge($fk, ['table' => $bareTable]);
                continue;
            }

            try {
                Schema::connection($targetConn)->table($bareTable, function ($blueprint) use ($fk, $bareRefTable) {
                    $blueprint->foreign($fk['column'])
                        ->references($fk['foreign_column'])
                        ->on($bareRefTable);
                });
            } catch (\Exception $e) {
                Log::warning("FK binding failed for {$bareTable}.{$fk['column']}: " . $e->getMessage());
                $deferred[] = array_merge($fk, ['table' => $bareTable]);
            }
        }

        return $deferred;
    }
    }

    /**
     * Create a table on target using native DDL from source (same driver).
     */
    private function createTableNative(
        string $sourceConn,
        string $targetConn,
        string $table,
        string $driver
    ): array {
        $ddl = $this->getCreateTableSQL($sourceConn, $table);

        if (!$ddl) {
            return [
                'success' => false,
                'message' => "Could not retrieve DDL for table {$table}",
            ];
        }

        try {
            DB::connection($targetConn)->statement($ddl);

            return [
                'success' => true,
                'message' => "Created table {$table} successfully",
            ];
        } catch (\Exception $e) {
            // If native DDL fails, fall back to Schema Builder
            Log::warning("Native DDL failed for {$table}, falling back to Schema Builder: " . $e->getMessage());
            return $this->createTableViaSchemaBuilder($sourceConn, $targetConn, $table);
        }
    }

    // ────────────────────────────────────────────────────────────────
    //  Schema Builder Fallback (cross-driver)
    // ────────────────────────────────────────────────────────────────

    /**
     * Create a table on target using Laravel's Schema Builder.
     * Reads column metadata from source and builds the table programmatically.
     */
    private function createTableViaSchemaBuilder(
        string $sourceConn,
        string $targetConn,
        string $table
    ): array {
        $bareTable = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;

        $columns = Schema::connection($sourceConn)->getColumns($table);

        if (empty($columns)) {
            return [
                'success' => false,
                'message' => "No columns found for table {$table} in source",
            ];
        }

        try {
            Schema::connection($targetConn)->create($bareTable, function ($blueprint) use ($columns) {
                foreach ($columns as $col) {
                    $this->addColumnToBlueprint($blueprint, $col);
                }
            });

            return [
                'success' => true,
                'message' => "Created table {$bareTable} successfully (via Schema Builder)",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Failed to create table {$bareTable}: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Map a column metadata array to a Blueprint column definition.
     * Handles the most common column types across drivers.
     */
    private function addColumnToBlueprint($blueprint, array $col): void
    {
        $name = $col['name'];
        $typeName = strtolower($col['type_name'] ?? $col['type'] ?? 'varchar');
        $nullable = $col['nullable'] ?? false;
        $default = $col['default'] ?? null;
        $autoIncrement = $col['auto_increment'] ?? false;

        // Map database type to Blueprint method
        $column = match (true) {
            $autoIncrement && str_contains($typeName, 'big') => $blueprint->bigIncrements($name),
            $autoIncrement => $blueprint->increments($name),
            str_contains($typeName, 'bigint') || $typeName === 'int8' => $blueprint->bigInteger($name),
            str_contains($typeName, 'smallint') || $typeName === 'int2' => $blueprint->smallInteger($name),
            str_contains($typeName, 'tinyint') => $blueprint->tinyInteger($name),
            str_contains($typeName, 'int') || $typeName === 'integer' || $typeName === 'int4' => $blueprint->integer($name),
            str_contains($typeName, 'decimal') || str_contains($typeName, 'numeric') => $blueprint->decimal($name),
            str_contains($typeName, 'float') || str_contains($typeName, 'real') || $typeName === 'float4' => $blueprint->float($name),
            str_contains($typeName, 'double') || $typeName === 'float8' => $blueprint->double($name),
            $typeName === 'boolean' || $typeName === 'bool' => $blueprint->boolean($name),
            $typeName === 'date' => $blueprint->date($name),
            $typeName === 'datetime' || str_contains($typeName, 'timestamp') => $blueprint->dateTime($name),
            $typeName === 'time' => $blueprint->time($name),
            str_contains($typeName, 'text') || $typeName === 'mediumtext' || $typeName === 'longtext' => $blueprint->text($name),
            str_contains($typeName, 'blob') || $typeName === 'bytea' => $blueprint->binary($name),
            str_contains($typeName, 'json') => $blueprint->json($name),
            str_contains($typeName, 'uuid') => $blueprint->uuid($name),
            default => $blueprint->string($name),
        };

        if ($nullable) {
            $column->nullable();
        }

        // Set default value (skip expressions like CURRENT_TIMESTAMP, nextval, etc.)
        if ($default !== null && !$this->isExpressionDefault($default)) {
            $column->default($this->parseDefaultValue($default));
        }
    }

    /**
     * Check if a default value is a database expression (not a literal).
     */
    private function isExpressionDefault(mixed $default): bool
    {
        if (!is_string($default)) {
            return false;
        }

        $upper = strtoupper(trim($default));

        return str_contains($upper, 'CURRENT_TIMESTAMP')
            || str_contains($upper, 'NOW()')
            || str_contains($upper, 'NEXTVAL')
            || str_contains($upper, 'UUID()')
            || str_starts_with($upper, '(')
            || str_contains($upper, '::');
    }

    /**
     * Parse a default value string to its actual PHP value.
     */
    private function parseDefaultValue(mixed $default): mixed
    {
        if (!is_string($default)) {
            return $default;
        }

        // Strip surrounding quotes
        $trimmed = trim($default, "'\"");

        // Numeric values
        if (is_numeric($trimmed)) {
            return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
        }

        // Boolean-ish
        if (in_array(strtoupper($trimmed), ['TRUE', '1'], true)) {
            return true;
        }
        if (in_array(strtoupper($trimmed), ['FALSE', '0'], true)) {
            return false;
        }

        // NULL
        if (strtoupper($trimmed) === 'NULL') {
            return null;
        }

        return $trimmed;
    }
}
