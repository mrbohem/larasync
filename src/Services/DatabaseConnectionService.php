<?php

namespace MrBohem\Larasync\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseConnectionService
{
    /**
     * Check if a database prefix (db1/db2) has required config values.
     * Checks saved UI settings first, then falls back to config/env.
     */
    public function isConfigured(string $prefix): bool
    {
        // Check saved settings first
        $settingsService = app(SettingsService::class);
        if ($settingsService->has()) {
            $settings = $settingsService->load();
            $driver = $settings[$prefix]['driver'] ?? null;
            $database = $settings[$prefix]['database'] ?? null;
            if (!empty($driver) && !empty($database)) {
                return true;
            }
        }

        // Fall back to config/env
        $driver = config("larasync.{$prefix}.driver");
        $database = config("larasync.{$prefix}.database");

        return !empty($driver) && !empty($database);
    }

    /**
     * Get the default schema for a given database driver.
     * - PostgreSQL: 'public'
     * - MySQL: null (not applicable, uses database as namespace)
     * - SQLite: null (not applicable)
     */
    public function getDefaultSchema(string $driver): ?string
    {
        return match($driver) {
            'pgsql' => 'public',
            default => null,
        };
    }

    /**
     * Build a Laravel database connection config array.
     */
    public function buildConfig(
        string $driver,
        ?string $host,
        ?string $port,
        ?string $database,
        ?string $username,
        ?string $password,
        ?string $schema = null
    ): ?array {
        if (!$driver) {
            return null;
        }

        // Use provided schema or get default for the driver
        if ($schema === null) {
            $schema = $this->getDefaultSchema($driver);
        }

        $config = [
            'driver' => $driver,
            'database' => $database,
            'prefix' => '',
        ];

        // Store schema if provided (for PostgreSQL)
        if ($schema) {
            $config['schema'] = $schema;
        }

        // Add charset and collation only for MySQL
        if ($driver === 'mysql') {
            $config['charset'] = 'utf8mb4';
            $config['collation'] = 'utf8mb4_unicode_ci';
            $config['strict'] = true;
        }

        // PostgreSQL doesn't use charset/collation in the same way
        if ($driver === 'pgsql') {
            $config['sslmode'] = env('DB_SSL_MODE', 'prefer');
            $config['search_path'] = $schema ?? 'public';
        }

        if ($driver !== 'sqlite') {
            // Set default port based on driver if not provided
            if (empty($port)) {
                $port = match($driver) {
                    'mysql' => '3306',
                    'pgsql' => '5432',
                    default => $port,
                };
            }

            $config = array_merge($config, [
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
            ]);
        }

        if ($driver === 'sqlite') {
            $config['database'] = database_path($config['database']);
        }

        return $config;
    }

    /**
     * Test if a connection config actually works.
     */
    public function testConnection(array $config, string $name = 'temp'): bool
    {
        try {
            $connectionName = "temp_{$name}";
            Config::set("database.connections.{$connectionName}", $config);
            DB::purge($connectionName);

            DB::connection($connectionName)->getPdo()->query('SELECT 1');

            return true;
        } catch (\Exception $e) {
            Log::error("{$name} test connection failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register a temporary connection in Laravel's config and purge cache.
     */
    public function registerConnection(string $name, array $config): void
    {
        Config::set("database.connections.{$name}", $config);
        DB::purge($name);
    }
}
