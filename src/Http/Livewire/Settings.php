<?php

namespace MrBohem\Larasync\Http\Livewire;

use Livewire\Component;
use Illuminate\Validation\Rule;
use MrBohem\Larasync\Services\SettingsService;
use MrBohem\Larasync\Services\DatabaseConnectionService;

class Settings extends Component
{
    // ── DB1 Fields ────────────────────────────────────────────────
    public $db1_driver = 'mysql';
    public $db1_host = '';
    public $db1_port = '';
    public $db1_database = '';
    public $db1_username = '';
    public $db1_password = '';
    public $db1_schema = '';

    // ── DB2 Fields ────────────────────────────────────────────────
    public $db2_driver = 'mysql';
    public $db2_host = '';
    public $db2_port = '';
    public $db2_database = '';
    public $db2_username = '';
    public $db2_password = '';
    public $db2_schema = '';

    // ── UI State ──────────────────────────────────────────────────
    public $db1_test_status = null; // null, 'success', 'error'
    public $db2_test_status = null;
    public $db1_test_message = '';
    public $db2_test_message = '';
    public $save_status = null;
    public $save_message = '';
    public $settings_source = 'none'; // 'json', 'env', 'none'

    // ── Ignored Tables ────────────────────────────────────────────
    public $ignored_tables = '';
    public $new_ignored_table = '';

    // ── Performance / Chunking ─────────────────────────────────----
    public $performance_chunk_size = 1000;
    public $performance_batch_insert_size = 500;
    public $performance_progress_chunk_size = 5000;

    // ── Services ──────────────────────────────────────────────────
    private SettingsService $settingsService;
    private DatabaseConnectionService $connectionService;

    public function boot()
    {
        $this->settingsService = app(SettingsService::class);
        $this->connectionService = app(DatabaseConnectionService::class);
    }

    public function mount()
    {
        $this->loadSettings();
    }

    /**
     * Load settings from JSON file first, then fall back to config/env.
     */
    private function loadSettings()
    {
        if ($this->settingsService->has()) {
            $this->settings_source = 'json';
            $settings = $this->settingsService->load();

            foreach (['db1', 'db2'] as $db) {
                foreach (['driver', 'host', 'port', 'database', 'username', 'password', 'schema'] as $field) {
                    $key = "{$db}.{$field}";
                    $property = "{$db}_{$field}";
                    $this->{$property} = $settings[$db][$field] ?? '';
                }
            }

            // Load ignored tables from saved settings
            $tables = $settings['ignored_tables'] ?? config('larasync.ignored_tables', []);
            $this->ignored_tables = is_array($tables) ? implode(', ', $tables) : $tables;
            // Load performance settings
            $this->performance_chunk_size = $settings['performance']['chunk_size'] ?? $this->performance_chunk_size;
            $this->performance_batch_insert_size = $settings['performance']['batch_insert_size'] ?? $this->performance_batch_insert_size;
            $this->performance_progress_chunk_size = $settings['performance']['progress_chunk_size'] ?? $this->performance_progress_chunk_size;
        } else {
            // Fall back to config (env) values
            $hasEnv = false;
            foreach (['db1', 'db2'] as $db) {
                foreach (['driver', 'host', 'port', 'database', 'username', 'password', 'schema'] as $field) {
                    $value = config("larasync.{$db}.{$field}");
                    $this->{"{$db}_{$field}"} = $value ?? '';
                    if (!empty($value) && $field !== 'driver') {
                        $hasEnv = true;
                    }
                }
            }
            $this->settings_source = $hasEnv ? 'env' : 'none';

            // Load ignored tables from config
            $tables = config('larasync.ignored_tables', []);
            $this->ignored_tables = implode(', ', $tables);
            // Load performance defaults from config if present
            $this->performance_chunk_size = config('larasync.performance.chunk_size', $this->performance_chunk_size);
            $this->performance_batch_insert_size = config('larasync.performance.batch_insert_size', $this->performance_batch_insert_size);
            $this->performance_progress_chunk_size = config('larasync.performance.progress_chunk_size', $this->performance_progress_chunk_size);
        }
    }

    /**
     * Save all settings to JSON file.
     */
    public function saveSettings()
    {
        $this->validate([
            'db1_driver' => ['required', Rule::in(['sqlite', 'mysql', 'pgsql'])],
            'db1_database' => ['required', function($attribute, $value, $fail) {
                if ($this->db1_driver === 'sqlite' && !file_exists($value)) {
                    $fail('SQLite file path does not exist.');
                }
            }],
            'db2_driver' => ['required', Rule::in(['sqlite', 'mysql', 'pgsql'])],
            'db2_database' => ['required', function($attribute, $value, $fail) {
                if ($this->db2_driver === 'sqlite' && !file_exists($value)) {
                    $fail('SQLite file path does not exist.');
                }
            }],
            'db1_host' => ['required_unless:db1_driver,sqlite'],
            'db1_port' => 'nullable|numeric',
            'db1_username' => ['required_unless:db1_driver,sqlite'],
            'db2_host' => ['required_unless:db2_driver,sqlite'],
            'db2_port' => 'nullable|numeric',
            'db2_username' => ['required_unless:db2_driver,sqlite'],
            'performance_chunk_size' => 'nullable|numeric|min:1',
            'performance_batch_insert_size' => 'nullable|numeric|min:1',
            'performance_progress_chunk_size' => 'nullable|numeric|min:1',
        ]);

        try {

            $settings = [];
            foreach (['db1', 'db2'] as $db) {
                foreach (['driver', 'host', 'port', 'database', 'username', 'password', 'schema'] as $field) {
                    $settings[$db][$field] = $this->{"{$db}_{$field}"};
                }
            }

            // Parse ignored tables from comma-separated string
            $settings['ignored_tables'] = array_values(array_filter(
                array_map('trim', explode(',', $this->ignored_tables))
            ));

            // Save performance settings
            $settings['performance'] = [
                'chunk_size' => (int) $this->performance_chunk_size,
                'batch_insert_size' => (int) $this->performance_batch_insert_size,
                'progress_chunk_size' => (int) $this->performance_progress_chunk_size,
            ];

            $this->settingsService->save($settings);

            $this->settings_source = 'json';
            $this->save_status = 'success';
            $this->save_message = 'Settings saved successfully!';

            // Reset test statuses since settings changed
            $this->db1_test_status = null;
            $this->db2_test_status = null;

        } catch (\Exception $e) {
            $this->save_status = 'error';
            $this->save_message = 'Failed to save: ' . $e->getMessage();
        }
    }

    /**
     * Test a database connection using current form values.
     */
    public function testConnection(string $prefix)
    {
        $driver = $this->{"{$prefix}_driver"};
        $host = $this->{"{$prefix}_host"};
        $port = $this->{"{$prefix}_port"};
        $database = $this->{"{$prefix}_database"};
        $username = $this->{"{$prefix}_username"};
        $password = $this->{"{$prefix}_password"};
        $schema = $this->{"{$prefix}_schema"};

        if (empty($driver) || empty($database)) {
            $this->{"{$prefix}_test_status"} = 'error';
            $this->{"{$prefix}_test_message"} = 'Driver and database are required.';
            return;
        }

        $config = $this->connectionService->buildConfig($driver, $host, $port, $database, $username, $password, $schema);

        if (!$config) {
            $this->{"{$prefix}_test_status"} = 'error';
            $this->{"{$prefix}_test_message"} = 'Invalid configuration.';
            return;
        }

        $connected = $this->connectionService->testConnection($config, $prefix);

        $this->{"{$prefix}_test_status"} = $connected ? 'success' : 'error';
        $this->{"{$prefix}_test_message"} = $connected
            ? 'Connection successful!'
            : 'Connection failed. Please check your credentials.';
    }

    /**
     * Reset all settings and delete the JSON file.
     */
    public function resetSettings()
    {
        $this->settingsService->delete();

        // Reload from env
        foreach (['db1', 'db2'] as $db) {
            foreach (['driver', 'host', 'port', 'database', 'username', 'password', 'schema'] as $field) {
                $value = config("larasync.{$db}.{$field}");
                $this->{"{$db}_{$field}"} = $value ?? '';
            }
        }

        $this->settings_source = 'none';
        $this->db1_test_status = null;
        $this->db2_test_status = null;
        $this->save_status = 'success';
        $this->save_message = 'Settings reset to defaults.';

        // Reset ignored tables to config defaults
        $tables = config('larasync.ignored_tables', []);
        $this->ignored_tables = implode(', ', $tables);
    }

    /**
     * Get the default port for a given driver.
     */
    public function updatedDb1Driver()
    {
        $this->setDefaultPort('db1');
    }

    public function updatedDb2Driver()
    {
        $this->setDefaultPort('db2');
    }

    private function setDefaultPort(string $prefix)
    {
        $driver = $this->{"{$prefix}_driver"};
        $this->{"{$prefix}_port"} = match($driver) {
            'mysql' => '3306',
            'pgsql' => '5432',
            default => '',
        };

        // Clear host/port/username/password for sqlite
        if ($driver === 'sqlite') {
            $this->{"{$prefix}_host"} = '';
            $this->{"{$prefix}_username"} = '';
            $this->{"{$prefix}_password"} = '';
        }

        // Reset test status
        $this->{"{$prefix}_test_status"} = null;
    }

    /**
     * Add a table to the ignored list.
     */
    public function addIgnoredTable()
    {
        $table = trim($this->new_ignored_table);
        if (empty($table)) {
            return;
        }

        $tables = array_filter(array_map('trim', explode(',', $this->ignored_tables)));
        if (!in_array($table, $tables)) {
            $tables[] = $table;
            $this->ignored_tables = implode(', ', $tables);
        }
        $this->new_ignored_table = '';
    }

    /**
     * Remove a table from the ignored list.
     */
    public function removeIgnoredTable(string $table)
    {
        $tables = array_filter(array_map('trim', explode(',', $this->ignored_tables)));
        $tables = array_values(array_filter($tables, fn($t) => $t !== $table));
        $this->ignored_tables = implode(', ', $tables);
    }

    /**
     * Get ignored tables as array (helper for the view).
     */
    public function getIgnoredTablesListProperty(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->ignored_tables))));
    }

    public function render()
    {
        return view('larasync::livewire.settings')
            ->layout('larasync::layouts.app');
    }
}
