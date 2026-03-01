<?php

namespace MrBohem\Larasync\Services;

class ConnectionLabelService
{
    /**
     * Determine a human-readable label ("Local" or "Cloud") for a database connection.
     */
    public function getLabel(string $driver, ?string $host): string
    {
        // SQLite is always local
        if ($driver === 'sqlite') {
            return 'Local';
        }

        // Localhost variants are local
        $localHosts = ['localhost', '127.0.0.1', '::1', ''];
        if (empty($host) || in_array(strtolower(trim($host)), $localHosts, true)) {
            return 'Local';
        }

        return 'Cloud';
    }

    /**
     * Build a disambiguated display name like "DB1 · host" or "DB1 · dbname".
     */
    public function buildDisplayName(string $prefix, string $driver, ?string $host, ?string $database): string
    {
        $label = strtoupper($prefix);

        // For SQLite, use the database filename
        if ($driver === 'sqlite' && $database) {
            return "{$label} · " . basename($database);
        }

        // For network DBs, use host (most distinguishing)
        if ($host) {
            return "{$label} · {$host}";
        }

        // Fallback to database name
        if ($database) {
            return "{$label} · {$database}";
        }

        return $label;
    }

    /**
     * Compute labels and display names for both database connections.
     *
     * @param  array{driver: ?string, host: ?string, database: ?string}  $db1Props
     * @param  array{driver: ?string, host: ?string, database: ?string}  $db2Props
     * @return array{db1_label: string, db2_label: string, labels_match: bool, db1_display: string, db2_display: string}
     */
    public function computeLabels(array $db1Props, array $db2Props): array
    {
        $db1Label = $this->getLabel($db1Props['driver'] ?? '', $db1Props['host'] ?? null);
        $db2Label = $this->getLabel($db2Props['driver'] ?? '', $db2Props['host'] ?? null);
        $labelsMatch = ($db1Label === $db2Label);

        if ($labelsMatch) {
            $db1Display = $this->buildDisplayName('db1', $db1Props['driver'] ?? '', $db1Props['host'] ?? null, $db1Props['database'] ?? null);
            $db2Display = $this->buildDisplayName('db2', $db2Props['driver'] ?? '', $db2Props['host'] ?? null, $db2Props['database'] ?? null);
        } else {
            $db1Display = $db1Label;
            $db2Display = $db2Label;
        }

        return [
            'db1_label' => $db1Label,
            'db2_label' => $db2Label,
            'labels_match' => $labelsMatch,
            'db1_display' => $db1Display,
            'db2_display' => $db2Display,
        ];
    }
}
