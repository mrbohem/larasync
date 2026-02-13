<?php

namespace MrBohem\Larasync\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseConnectionService
{
    /**
     * Check if a database prefix (db1/db2) has required config values.
     */
    public function isConfigured(string $prefix): bool
    {
        $driver = config("larasync.{$prefix}.driver");
        $database = config("larasync.{$prefix}.database");

        return !empty($driver) && !empty($database);
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
        ?string $password
    ): ?array {
        if (!$driver) {
            return null;
        }

        $config = [
            'driver' => $driver,
            'database' => $database,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ];

        if ($driver !== 'sqlite') {
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
