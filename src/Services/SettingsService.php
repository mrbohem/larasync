<?php

namespace MrBohem\Larasync\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class SettingsService
{
    private const SETTINGS_PATH = 'larasync/settings.json';
    private const UPLOADS_PATH = 'larasync/uploads';

    /**
     * Check if a settings file exists.
     */
    public function has(): bool
    {
        return Storage::disk('local')->exists(self::SETTINGS_PATH);
    }

    /**
     * Load settings from JSON file with decrypted passwords.
     */
    public function load(): array
    {
        if (!$this->has()) {
            return [];
        }

        $content = Storage::disk('local')->get(self::SETTINGS_PATH);
        $settings = json_decode($content, true);

        if (!is_array($settings)) {
            return [];
        }

        // Decrypt passwords
        foreach (['db1', 'db2'] as $db) {
            if (isset($settings[$db]['password']) && !empty($settings[$db]['password'])) {
                try {
                    $settings[$db]['password'] = Crypt::decryptString($settings[$db]['password']);
                } catch (\Exception $e) {
                    // If decryption fails, the key may have changed — return empty password
                    $settings[$db]['password'] = '';
                }
            }
        }

        return $settings;
    }

    /**
     * Save settings to JSON file with encrypted passwords.
     */
    public function save(array $settings): void
    {
        // Encrypt passwords before saving
        foreach (['db1', 'db2'] as $db) {
            if (isset($settings[$db]['password']) && !empty($settings[$db]['password'])) {
                $settings[$db]['password'] = Crypt::encryptString($settings[$db]['password']);
            }
        }

        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        Storage::disk('local')->put(self::SETTINGS_PATH, $json);
    }

    /**
     * Get a specific setting value using dot notation.
     *
     * @param string $key  e.g. 'db1.host', 'db2.driver'
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->load();
        return Arr::get($settings, $key, $default);
    }

    /**
     * Delete the settings file.
     */
    public function delete(): void
    {
        if ($this->has()) {
            Storage::disk('local')->delete(self::SETTINGS_PATH);
        }
    }

    /**
     * Handle SQLite file upload and return the stored path.
     */
    public function handleSqliteUpload($file, string $prefix): string
    {
        $filename = "{$prefix}_" . time() . '.' . $file->getClientOriginalExtension();
        $file->storeAs(self::UPLOADS_PATH, $filename, 'local');

        return storage_path('app/' . self::UPLOADS_PATH . '/' . $filename);
    }
}
