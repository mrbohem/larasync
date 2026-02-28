<?php

namespace MrBohem\Larasync\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use MrBohem\Larasync\LarasyncServiceProvider;

class TestCase extends Orchestra
{
    /**
     * Track temp database files for cleanup.
     */
    protected array $tempDbFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'MrBohem\\Larasync\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function tearDown(): void
    {
        // Clean up temp SQLite files
        foreach ($this->tempDbFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        $this->tempDbFiles = [];

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            LarasyncServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Default testing connection (in-memory SQLite)
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // App key needed for Livewire component rendering (sessions use encryption)
        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
    }

    // ── Helpers ────────────────────────────────────────────────────

    /**
     * Create a temp file-based SQLite database, register it as a named connection,
     * create a test table, and seed it with data.
     *
     * File-based SQLite is needed because the services internally re-register
     * connections with different names (e.g. sync_source, temp_compare1).
     * In-memory databases are connection-scoped, so re-registering loses data.
     *
     * @return array The connection config (suitable for passing to services)
     */
    protected function setupTestDatabase(string $connectionName, array $rows = [], string $tableName = 'users'): array
    {
        $dbPath = tempnam(sys_get_temp_dir(), 'larasync_test_') . '.sqlite';
        touch($dbPath);
        $this->tempDbFiles[] = $dbPath;

        $config = [
            'driver' => 'sqlite',
            'database' => $dbPath,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ];

        config()->set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        // Create the test table
        Schema::connection($connectionName)->create($tableName, function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        // Seed data
        if (!empty($rows)) {
            DB::connection($connectionName)->table($tableName)->insert($rows);
        }

        return $config;
    }

    /**
     * Generate sample user rows for testing.
     */
    protected function makeUserRows(int $count = 3): array
    {
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];
        }

        return $rows;
    }

    /**
     * Create a temp file-based SQLite database with two tables that have a FK relationship.
     * Creates: users (id, name, email) and posts (id, title, user_id → users.id)
     *
     * @return array The connection config
     */
    protected function setupTestDatabaseWithFK(string $connectionName, array $userRows = [], array $postRows = []): array
    {
        $dbPath = tempnam(sys_get_temp_dir(), 'larasync_test_') . '.sqlite';
        touch($dbPath);
        $this->tempDbFiles[] = $dbPath;

        $config = [
            'driver' => 'sqlite',
            'database' => $dbPath,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ];

        config()->set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        // Enable FK enforcement for SQLite
        DB::connection($connectionName)->statement('PRAGMA foreign_keys = ON');

        // Create users table (parent)
        Schema::connection($connectionName)->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        // Create posts table (child) with FK to users
        Schema::connection($connectionName)->create('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
        });

        // Seed data
        if (!empty($userRows)) {
            DB::connection($connectionName)->table('users')->insert($userRows);
        }
        if (!empty($postRows)) {
            DB::connection($connectionName)->table('posts')->insert($postRows);
        }

        return $config;
    }
}
