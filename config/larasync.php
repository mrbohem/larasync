<?php

return [
    'db1' => [  // Database 1 (auto-detected as Local or Cloud)
        'driver' => env('LARASYNC_DB1_DRIVER', 'sqlite'),
        'host' => env('LARASYNC_DB1_HOST'),
        'port' => env('LARASYNC_DB1_PORT', '3306'),
        'database' => env('LARASYNC_DB1_DATABASE'),
        'username' => env('LARASYNC_DB1_USERNAME'),
        'password' => env('LARASYNC_DB1_PASSWORD', ''),
    ],

    'db2' => [  // Database 2 (auto-detected as Local or Cloud)
        'driver' => env('LARASYNC_DB2_DRIVER', 'mysql'),
        'host' => env('LARASYNC_DB2_HOST'),
        'port' => env('LARASYNC_DB2_PORT', '3306'),
        'database' => env('LARASYNC_DB2_DATABASE'),
        'username' => env('LARASYNC_DB2_USERNAME'),
        'password' => env('LARASYNC_DB2_PASSWORD', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Tables
    |--------------------------------------------------------------------------
    |
    | Tables listed here will be completely excluded from comparison and sync.
    | Add table names (without schema prefix) that should never be synced.
    |
    */
    'ignored_tables' => [
        'sessions',
        'telescope_entries_tags',
        'telescope_entries',
        'telescope_monitoring',
        'pulse_entries',
        'pulse_values',
        'pulse_aggregates'
    ],
];
